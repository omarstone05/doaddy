<?php

namespace App\Services\AI;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;

class AIService
{
    protected string $provider;
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->provider = PlatformSetting::get('ai_provider', 'openai');
        
        if ($this->provider === 'openai') {
            $this->apiKey = PlatformSetting::get('openai_api_key');
            $this->model = PlatformSetting::get('openai_model', 'gpt-4o');
        } else {
            $this->apiKey = PlatformSetting::get('anthropic_api_key');
            $this->model = PlatformSetting::get('anthropic_model', 'claude-sonnet-4-20250514');
        }
    }

    /**
     * Send a chat message and get response
     */
    public function chat(array $messages, int $maxTokens = 1000): array
    {
        if (!$this->apiKey) {
            throw new \Exception('API key not configured. Please set it in System Settings.');
        }

        if ($this->provider === 'openai') {
            return $this->chatOpenAI($messages, $maxTokens);
        } else {
            return $this->chatAnthropic($messages, $maxTokens);
        }
    }

    /**
     * OpenAI Chat
     */
    protected function chatOpenAI(array $messages, int $maxTokens): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'tokens' => $data['usage']['total_tokens'] ?? 0,
            'model' => $data['model'] ?? $this->model,
        ];
    }

    /**
     * Anthropic Chat
     */
    protected function chatAnthropic(array $messages, int $maxTokens): array
    {
        // Convert OpenAI format to Anthropic format
        $anthropicMessages = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                continue; // Anthropic handles system via separate field
            }
            $anthropicMessages[] = [
                'role' => $message['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $message['content'],
            ];
        }

        // Extract system message if exists
        $systemMessage = collect($messages)->firstWhere('role', 'system')['content'] ?? '';

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'system' => $systemMessage,
            'messages' => $anthropicMessages,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Anthropic API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'content' => $data['content'][0]['text'] ?? '',
            'tokens' => $data['usage']['input_tokens'] + $data['usage']['output_tokens'],
            'model' => $data['model'] ?? $this->model,
        ];
    }

    /**
     * Quick helper for single message
     */
    public function ask(string $prompt, ?string $systemMessage = null): string
    {
        $messages = [];
        
        if ($systemMessage) {
            $messages[] = ['role' => 'system', 'content' => $systemMessage];
        }
        
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $response = $this->chat($messages);
        
        return $response['content'];
    }
}

