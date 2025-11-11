<?php

namespace App\Http\Controllers;

use App\Models\AddyChatMessage;
use App\Services\Addy\AddyCommandParser;
use App\Services\Addy\AddyResponseGenerator;
use App\Services\Addy\DocumentProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            
            foreach ($request->file('files') as $file) {
                try {
                    $processed = $processor->processFile($file, $organization->id);
                    $attachments[] = [
                        'file_path' => $processed['file_path'],
                        'file_name' => $processed['file_name'],
                        'file_size' => $processed['file_size'],
                        'mime_type' => $processed['mime_type'],
                    ];
                    
                    if (!empty($processed['extracted_data'])) {
                        $extractedData[] = $processed['extracted_data'];
                    }
                } catch (\Exception $e) {
                    \Log::error('File processing error', ['error' => $e->getMessage()]);
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

        // Get recent chat history for context
        $history = AddyChatMessage::getRecentHistory($organization->id, $user->id, 5);

        // Generate response
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
}

