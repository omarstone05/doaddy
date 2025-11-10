<?php

namespace App\Http\Controllers;

use App\Models\AddyChatMessage;
use App\Services\Addy\AddyCommandParser;
use App\Services\Addy\AddyResponseGenerator;
use Illuminate\Http\Request;

class AddyChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $organization = $request->user()->organization;
        $user = $request->user();

        if (!$organization) {
            return response()->json(['error' => 'No organization found'], 400);
        }

        // Save user message
        $userMessage = AddyChatMessage::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $request->message,
        ]);
        
        $chatMessageId = $userMessage->id;

        // Parse command
        $parser = new AddyCommandParser();
        $intent = $parser->parse($request->message);

        // Get recent chat history for context
        $history = AddyChatMessage::getRecentHistory($organization->id, $user->id, 5);

        // Generate response
        $generator = new AddyResponseGenerator($organization, $user);
        $response = $generator->generateResponse(
            $intent, 
            $request->message,
            $history->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])->toArray()
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

