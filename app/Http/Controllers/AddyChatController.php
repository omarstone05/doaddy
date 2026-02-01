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
use App\Services\ContextAwareOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AddyChatController extends Controller
{
    protected ContextAwareOcrService $ocrService;

    public function __construct(ContextAwareOcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required_without:files|string|max:1000',
            'files' => 'nullable|array|max:5',
            'files.*' => 'file|mimes:jpeg,jpg,png,gif,pdf,doc,docx,xls,xlsx,txt|max:10240', // 10MB max
        ]);

        $organization = $request->user()->organization;
        $user = $request->user();

        if (!$organization) {
            return response()->json(['error' => 'No organization found'], 400);
        }

        // Process file uploads with OCR
        $attachments = [];
        $extractedData = [];
        $ocrResults = [];
        $organizationId = session('current_organization_id') ?? $organization->id;
        
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                try {
                    $mimeType = $file->getMimeType();
                    $isOcrCapable = in_array($mimeType, [
                        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                        'application/pdf'
                    ]);
                    
                    if ($isOcrCapable) {
                        // Store file temporarily and process with OCR
                        $filePath = $file->store('temp/chat-uploads');
                        $fullPath = Storage::path($filePath);
                        
                        $ocrResult = $this->ocrService->processDocumentWithContext(
                            $fullPath,
                            [
                                'user_id' => $user->id,
                                'organization_id' => $organizationId,
                            ],
                            false // not historical
                        );
                        
                        if ($ocrResult['success'] ?? false) {
                            $ocrResults[] = [
                                'file_name' => $file->getClientOriginalName(),
                                'file_path' => $filePath,
                                'document_type' => $ocrResult['document_type'] ?? 'unknown',
                                'data' => $ocrResult['data'] ?? [],
                                'confidence' => $ocrResult['confidence'] ?? 0,
                                'auto_importable' => $ocrResult['auto_importable'] ?? false,
                                'requires_review' => $ocrResult['requires_review'] ?? true,
                                'questions' => $ocrResult['questions'] ?? [],
                                'uncertainty_analysis' => $ocrResult['uncertainty_analysis'] ?? null,
                            ];
                            
                            // Add to extracted data for AI context
                            $extractedData[] = [
                                'type' => 'ocr_document',
                                'document_type' => $ocrResult['document_type'] ?? 'unknown',
                                'data' => $ocrResult['data'] ?? [],
                                'confidence' => $ocrResult['confidence'] ?? 0,
                                'file_name' => $file->getClientOriginalName(),
                            ];
                            
                            $attachments[] = [
                                'file_path' => $filePath,
                                'file_name' => $file->getClientOriginalName(),
                                'file_size' => $file->getSize(),
                                'mime_type' => $mimeType,
                                'status' => 'processed',
                                'ocr_result' => $ocrResult,
                            ];
                        } else {
                            // OCR failed, fall back to basic extraction
                            $attachments[] = [
                                'file_path' => $filePath,
                                'file_name' => $file->getClientOriginalName(),
                                'file_size' => $file->getSize(),
                                'mime_type' => $mimeType,
                                'status' => 'ocr_failed',
                                'error' => $ocrResult['error'] ?? 'OCR processing failed',
                            ];
                        }
                    } else {
                        // Non-OCR file - extract text if possible
                        try {
                            $parser = app(\App\Services\Files\FileParserService::class);
                            $text = $parser->extractText($file->getPathname(), $mimeType);
                            
                            if (!empty($text)) {
                                $extractedData[] = [
                                    'type' => 'document_content',
                                    'content' => substr($text, 0, 2000),
                                    'file_name' => $file->getClientOriginalName()
                                ];
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Text extraction failed: ' . $e->getMessage());
                        }
                        
                        $filePath = $file->store('temp/chat-uploads');
                        $attachments[] = [
                            'file_path' => $filePath,
                            'file_name' => $file->getClientOriginalName(),
                            'file_size' => $file->getSize(),
                            'mime_type' => $mimeType,
                            'status' => 'stored',
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::error('File processing error', [
                        'error' => $e->getMessage(),
                        'file' => $file->getClientOriginalName(),
                    ]);
                    
                    $attachments[] = [
                        'file_path' => null,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        // Build message content
        $messageContent = $request->message ?? '';
        if (!empty($ocrResults)) {
            $messageContent .= "\n\n[Document(s) analyzed]";
        }

        // Save user message with OCR results
        $userMessage = AddyChatMessage::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $messageContent ?: 'File uploaded',
            'attachments' => $attachments,
            'metadata' => [
                'extracted_data' => $extractedData,
                'ocr_results' => $ocrResults,
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
            $shouldStream = $request->boolean('stream');
            $generator = new AddyResponseGenerator($organization, $user);
            $response = $generator->generateResponse(
                $intent, 
                $enhancedMessage ?: 'File uploaded',
                $history->map(fn($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ])->toArray(),
                $extractedData, // Pass extracted data as context
                $shouldStream
            );
            
            // Handle Streaming Response
            if ($shouldStream && $response instanceof \Generator) {
                return response()->stream(function() use ($response, $organization, $user, $intent, $chatMessageId) {
                    $fullContent = '';
                    $quickActions = [];
                    $actionData = null; // Rename to avoid conflict with potential $action variable
                    
                    try {
                        foreach ($response as $chunk) {
                            echo "data: " . json_encode($chunk) . "\n\n";
                            if (ob_get_level() > 0) ob_flush();
                            flush();

                            if (isset($chunk['content'])) {
                                $fullContent .= $chunk['content'];
                            }
                            if (isset($chunk['quick_actions'])) {
                                $quickActions = $chunk['quick_actions'];
                            }
                            if (isset($chunk['action'])) {
                                $actionData = $chunk['action'];
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Stream error during iteration: ' . $e->getMessage());
                        echo "data: " . json_encode(['error' => 'Stream interrupted']) . "\n\n";
                    }
                    
                    echo "data: [DONE]\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                    
                    // Post-stream: Save assistant message
                    try {
                        // If action was created, link it to the chat message
                        if (isset($actionData['action_id'])) {
                            $actionModel = \App\Models\AddyAction::find($actionData['action_id']);
                            if ($actionModel) {
                                $actionModel->update(['chat_message_id' => $chatMessageId]);
                            }
                        }

                        AddyChatMessage::create([
                            'organization_id' => $organization->id,
                            'user_id' => $user->id,
                            'role' => 'assistant',
                            'content' => $fullContent ?? '', // Ensure content is not null
                            'metadata' => [
                                'intent' => $intent,
                                'quick_actions' => $quickActions,
                                'action' => $actionData,
                            ],
                        ]);
                    } catch (\Exception $e) {
                         \Log::error('Failed to save streamed message', ['error' => $e->getMessage()]);
                    }

                }, 200, [
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'Connection' => 'keep-alive',
                    'X-Accel-Buffering' => 'no',
                ]);
            }
            
            // Handle Standard (Blocking) Response
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

            // Organization creation is now handled by Penda Cloud
            $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
            
            // Save assistant response
            $assistantMessage = AddyChatMessage::create([
                'organization_id' => $currentOrgId,
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => "I'll help you create the organization '{$organizationName}'. Let me redirect you to Penda Cloud to complete the setup!",
                'metadata' => [
                    'intent' => $intent,
                    'organization_name' => $organizationName,
                    'redirect_to_penda_onboarding' => true,
                ],
            ]);

            return response()->json([
                'message' => $assistantMessage->load('user'),
                'quick_actions' => [],
                'action' => null,
                'redirect' => $pendaCloudUrl . '/onboarding/step-1',
                'organization_created' => false,
                'message_text' => 'Please create your organization through Penda Cloud onboarding.',
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
     * Import document from chat OCR result
     */
    public function importDocument(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
            'document_type' => 'required|string',
            'data' => 'required|array',
            'reviewed' => 'boolean',
        ]);

        $user = $request->user();
        $organization = $user->organization;
        
        if (!$organization) {
            return response()->json(['error' => 'No organization found'], 400);
        }

        $organizationId = session('current_organization_id') ?? $organization->id;
        $data = $request->data;
        $docType = strtolower($data['document_type'] ?? $request->document_type);

        \Log::info('Importing document from chat', [
            'document_type' => $docType,
            'data' => $data,
            'reviewed' => $request->boolean('reviewed'),
        ]);

        try {
            // Use EnhancedDataUploadController's import methods
            $importController = app(EnhancedDataUploadController::class);
            
            // Create a mock request for the import
            $importRequest = new Request();
            $importRequest->merge([
                'file_path' => $request->file_path,
                'document_type' => $docType,
                'data' => $data,
                'reviewed' => $request->boolean('reviewed'),
            ]);
            $importRequest->setUserResolver(fn() => $user);

            $result = $importController->importOcrReviewed($importRequest);
            $responseData = json_decode($result->getContent(), true);

            // If successful, save a chat message about the import
            if ($responseData['success'] ?? false) {
                $docTypeName = ucfirst(str_replace('_', ' ', $docType));
                $amount = $data['total'] ?? $data['amount'] ?? null;
                $amountStr = $amount ? ' (' . ($data['currency'] ?? 'ZMW') . ' ' . number_format($amount, 2) . ')' : '';
                
                AddyChatMessage::create([
                    'organization_id' => $organizationId,
                    'user_id' => $user->id,
                    'role' => 'assistant',
                    'content' => "✅ {$docTypeName} imported successfully{$amountStr}. " . ($responseData['message'] ?? ''),
                    'metadata' => [
                        'import_result' => $responseData,
                        'document_type' => $docType,
                    ],
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            \Log::error('Document import from chat failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
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
