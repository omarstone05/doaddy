<?php

namespace App\Http\Controllers;

use App\Models\AddyChatMessage;
use App\Models\Organization;
use App\Models\DashboardCard;
use App\Models\OrgDashboardCard;
use App\Services\Addy\AddyCommandParser;
use App\Services\Addy\AddyResponseGenerator;
use App\Services\Addy\DocumentProcessorService;
use App\Services\Document\DocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AddyChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'files' => 'nullable|array|max:5',
            'files.*' => 'file|mimes:jpeg,jpg,png,gif,pdf,doc,docx,xls,xlsx,txt|max:10240', // 10MB max
        ]);

        $organization = $request->user()->organization;
        $user = $request->user();

        if (!$organization) {
            return response()->json(['error' => 'No organization found'], 400);
        }

        // Process file uploads if any
        $attachments = [];
        $extractedData = [];
        
        if ($request->hasFile('files')) {
            $processor = new DocumentProcessorService();
            $storageService = new DocumentStorageService();
            
            foreach ($request->file('files') as $file) {
                try {
                    $processed = $processor->processFile($file, $organization->id);
                    $attachments[] = [
                        'file_path' => $processed['file_path'],
                        'file_name' => $processed['file_name'],
                        'file_size' => $processed['file_size'],
                        'mime_type' => $processed['mime_type'],
                        'extracted_data' => $processed['extracted_data'] ?? null,
                        'extracted_text' => $processed['extracted_text'] ?? null,
                    ];
                    
                if (!empty($processed['extracted_data'])) {
                    $extractedData[] = $processed['extracted_data'];
                    \Log::info('Extracted structured data from file', [
                        'file_name' => $processed['file_name'],
                        'document_type' => $processed['extracted_data']['document_type'] ?? 'unknown',
                        'has_amount' => isset($processed['extracted_data']['amount']),
                        'amount' => $processed['extracted_data']['amount'] ?? null,
                    ]);
                } elseif (!empty($processed['extracted_text'])) {
                    // If we have extracted text but no structured data, include it
                    $extractedData[] = [
                        'raw_text' => $processed['extracted_text'],
                        'document_type' => 'unknown',
                        'file_name' => $processed['file_name'],
                    ];
                    \Log::info('Using raw text extraction (no structured data)', [
                        'file_name' => $processed['file_name'],
                        'text_length' => strlen($processed['extracted_text']),
                    ]);
                }
                } catch (\Exception $e) {
                    \Log::error('File processing error', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'file' => $file->getClientOriginalName(),
                    ]);
                    
                    // Still save the file even if processing failed
                    $attachments[] = [
                        'file_path' => $file->store("chat-attachments/{$organization->id}", 'public'),
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'processing_error' => 'Failed to extract data: ' . $e->getMessage(),
                        'extracted_data' => null,
                        'extracted_text' => null,
                    ];
                }
            }
        }

        // Build message content
        $messageContent = $request->message ?? '';
        if (!empty($extractedData)) {
            $messageContent .= "\n\n[Document attached with extracted data]";
        }

        // Save user message
        $userMessage = AddyChatMessage::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $messageContent ?: 'File uploaded',
            'attachments' => $attachments,
            'metadata' => [
                'extracted_data' => $extractedData,
            ],
        ]);
        
        $chatMessageId = $userMessage->id;
        
        // Store attachments as proper Attachment records for historical context
        if (!empty($attachments)) {
            $storageService = new DocumentStorageService();
            foreach ($attachments as $attachmentData) {
                try {
                    $storageService->storeFromChat(
                        $attachmentData,
                        $organization->id,
                        $chatMessageId,
                        $user->id
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to store attachment record', ['error' => $e->getMessage()]);
                }
            }
        }

        // Build enhanced message with extracted data
        $enhancedMessage = $request->message ?? '';
        if (!empty($extractedData)) {
            $enhancedMessage .= "\n\nI've attached a document. Here's what I found:\n";
            foreach ($extractedData as $data) {
                if (isset($data['type']) && isset($data['amount'])) {
                    $currency = $data['currency'] ?? 'ZMW';
                    $enhancedMessage .= "- {$data['type']}: {$data['amount']} {$currency}";
                    if (isset($data['description'])) {
                        $enhancedMessage .= " - {$data['description']}";
                    }
                    if (isset($data['date'])) {
                        $enhancedMessage .= " on {$data['date']}";
                    }
                    $enhancedMessage .= "\n";
                }
            }
            $enhancedMessage .= "\nCan you help me add this as a transaction?";
        }

        // Parse command
        $parser = new AddyCommandParser();
        $intent = $parser->parse($enhancedMessage ?: 'File uploaded');

        // Handle organization creation specially (before response generator)
        if ($intent['intent'] === 'action' && $intent['action_type'] === 'create_organization') {
            \Log::info('Organization creation detected', [
                'intent' => $intent,
                'message' => $enhancedMessage ?: $request->message,
            ]);
            return $this->handleCreateOrganization($user, $intent, $enhancedMessage ?: $request->message, $userMessage);
        }

        // Get recent chat history for context
        $history = AddyChatMessage::getRecentHistory($organization->id, $user->id, 5);

        // Generate response
        try {
            $generator = new AddyResponseGenerator($organization, $user);
            $response = $generator->generateResponse(
                $intent, 
                $enhancedMessage ?: 'File uploaded',
                $history->map(fn($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ])->toArray(),
                $extractedData // Pass extracted data as context
            );
            
            // If action was created, link it to the chat message
            if (isset($response['action']['action_id'])) {
                $action = \App\Models\AddyAction::find($response['action']['action_id']);
                if ($action) {
                    $action->update(['chat_message_id' => $chatMessageId]);
                }
            }

            // Save assistant response
            $assistantMessage = AddyChatMessage::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => $response['content'],
                'metadata' => [
                    'intent' => $intent,
                    'quick_actions' => $response['quick_actions'] ?? [],
                    'action' => $response['action'] ?? null,
                ],
            ]);

            return response()->json([
                'message' => $assistantMessage->load('user'),
                'quick_actions' => $response['quick_actions'] ?? [],
                'action' => $response['action'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating Addy response', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Save error message
            $errorMessage = AddyChatMessage::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => "I encountered an error: " . $e->getMessage() . ". Please try rephrasing your request or provide more details.",
            ]);
            
            return response()->json([
                'message' => $errorMessage->load('user'),
                'quick_actions' => [],
                'action' => null,
            ]);
        }
    }

    public function getHistory(Request $request)
    {
        $organization = $request->user()->organization;
        $user = $request->user();

        if (!$organization) {
            return response()->json(['error' => 'No organization found'], 400);
        }

        $messages = AddyChatMessage::where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    public function clearHistory(Request $request)
    {
        $organization = $request->user()->organization;
        $user = $request->user();

        if (!$organization) {
            return response()->json(['error' => 'No organization found'], 400);
        }

        AddyChatMessage::where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Handle organization creation request from chat
     */
    protected function handleCreateOrganization($user, array $intent, string $message, $userMessage)
    {
        $params = $intent['parameters'] ?? [];
        $organizationName = $params['name'] ?? null;

        \Log::info('Handling organization creation', [
            'params' => $params,
            'organization_name' => $organizationName,
            'message' => $message,
        ]);

        // Get current organization ID before any operations
        $currentOrg = $user->organization;
        $currentOrgId = $currentOrg ? $currentOrg->id : null;

        // If no name provided, ask for it
        if (!$organizationName) {
            // Need an organization to save the message
            if (!$currentOrgId) {
                return response()->json([
                    'error' => 'You need to be part of an organization to use this feature. Please contact support.',
                ], 400);
            }

            $assistantMessage = AddyChatMessage::create([
                'organization_id' => $currentOrgId,
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => "I'd be happy to help you create a new organization! What would you like to name it?",
                'metadata' => [
                    'intent' => $intent,
                    'requires_followup' => true,
                ],
            ]);

            return response()->json([
                'message' => $assistantMessage->load('user'),
                'quick_actions' => [],
                'action' => null,
            ]);
        }

        try {
            // Validate organization name
            if (empty(trim($organizationName))) {
                throw new \Exception('Organization name cannot be empty.');
            }

            // Generate unique slug
            $baseSlug = Str::slug($organizationName);
            if (empty($baseSlug)) {
                // If slug is empty after slugging, use a fallback
                $baseSlug = 'organization-' . time();
            }
            $slug = $baseSlug;
            $counter = 1;
            
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            // Create organization
            $newOrganization = Organization::create([
                'id' => (string) Str::uuid(),
                'name' => trim($organizationName),
                'slug' => $slug,
                'tone_preference' => 'professional',
                'currency' => 'ZMW',
                'timezone' => 'Africa/Lusaka',
            ]);

            // Create default dashboard cards
            $this->createDefaultDashboardCards($newOrganization->id);

            // Add user to organization via pivot table
            $user->organizations()->attach($newOrganization->id, [
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);

            // Switch to new organization
            session(['current_organization_id' => $newOrganization->id]);
            $user->update(['organization_id' => $newOrganization->id]);

            // Save assistant response (use old org for this message since it was sent in that context)
            $assistantMessage = AddyChatMessage::create([
                'organization_id' => $currentOrgId ?? $newOrganization->id,
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => "Great! I've created the organization '{$organizationName}' and added you as the owner. Let's set it up!",
                'metadata' => [
                    'intent' => $intent,
                    'organization_created' => true,
                    'new_organization_id' => $newOrganization->id,
                    'redirect_to_onboarding' => true,
                ],
            ]);

            return response()->json([
                'message' => $assistantMessage->load('user'),
                'quick_actions' => [],
                'action' => null,
                'redirect' => '/onboarding',
                'organization_created' => true,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating organization from chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'organization_name' => $organizationName ?? 'null',
            ]);

            // Use current org ID for error message, or create a generic error if no org
            $errorOrgId = $currentOrgId;
            if (!$errorOrgId) {
                // If user has no org, we can't save a chat message, so return error directly
                return response()->json([
                    'error' => "I encountered an error while creating the organization: " . $e->getMessage() . ". Please try again or contact support.",
                ], 500);
            }

            $errorMessage = AddyChatMessage::create([
                'organization_id' => $errorOrgId,
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => "I encountered an error while creating the organization: " . $e->getMessage() . ". Please try again or contact support.",
            ]);

            return response()->json([
                'message' => $errorMessage->load('user'),
                'quick_actions' => [],
                'action' => null,
            ]);
        }
    }

    /**
     * Create default dashboard cards for a new organization
     */
    private function createDefaultDashboardCards(string $organizationId): void
    {
        $defaultCardKeys = [
            'total_revenue',
            'total_orders',
            'expenses_today',
            'net_balance',
            'revenue_chart',
            'cash_flow',
            'top_products',
            'top_customers',
            'recent_activity',
        ];

        $dashboardCards = DashboardCard::whereIn('key', $defaultCardKeys)
            ->where('is_active', true)
            ->get();

        foreach ($dashboardCards as $index => $card) {
            try {
                OrgDashboardCard::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organizationId,
                    'dashboard_card_id' => $card->id,
                    'config' => $card->default_config ?? [],
                    'display_order' => $index,
                    'is_visible' => true,
                    'width' => 8,
                    'height' => 8,
                    'row' => null,
                    'col' => null,
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to create dashboard card for organization', [
                    'organization_id' => $organizationId,
                    'card_key' => $card->key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
