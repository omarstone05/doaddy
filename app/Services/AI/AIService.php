<?php

namespace App\Services\AI;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Log;
use Penda\AIClient\PendaAIClient;
use Penda\JWT\PendaJWT;

class AIService
{
    protected PendaAIClient $client;
    protected PendaJWT $jwt;
    protected bool $useMicroservice;
    protected string $provider;
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->jwt = app(PendaJWT::class);
        $this->client = app('penda.ai');
        $this->useMicroservice = PlatformSetting::get('ai_service_enabled', true);
        
        // Set source app
        $this->client->setSourceApp('addy');
        
        // Legacy provider setup (for fallback)
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
     * Get JWT token for current user
     */
    protected function getJwtToken(): string
    {
        $user = auth()->user();
        
        if (!$user) {
            throw new \Exception('User must be authenticated to use AI service');
        }

        // Get user's penda_account_id (or use user ID as fallback)
        $pendaAccountId = $user->penda_account_id ?? (string) $user->id;
        
        return $this->jwt->generateForUser(
            $pendaAccountId,
            'addy',
            ['*'],
            [
                'email' => $user->email,
                'name' => $user->name,
                'organization_id' => $user->organization_id ?? $user->current_organization_id,
            ]
        );
    }

    /**
     * Send a chat message and get response
     */
    public function chat(array $messages, int $maxTokens = 1000): array
    {
        if (!$this->useMicroservice) {
            return $this->fallbackChat($messages, $maxTokens);
        }

        try {
            $token = $this->getJwtToken();
            $this->client->setToken($token);
            
            // Convert messages array to single message for microservice
            $userMessage = '';
            $context = [];
            
            foreach ($messages as $msg) {
                if ($msg['role'] === 'user') {
                    $userMessage = $msg['content'];
                } elseif ($msg['role'] === 'system') {
                    $context['system_prompt'] = $msg['content'];
                } elseif ($msg['role'] === 'assistant') {
                    if (!isset($context['history'])) {
                        $context['history'] = [];
                    }
                    $context['history'][] = ['role' => 'assistant', 'content' => $msg['content']];
                }
            }
            
            if (empty($userMessage)) {
                // Extract from last user message
                $userMessages = array_filter($messages, fn($m) => $m['role'] === 'user');
                $lastUserMsg = end($userMessages);
                $userMessage = $lastUserMsg['content'] ?? '';
            }
            
            $response = $this->client->chat($userMessage, $context);
            
            return [
                'content' => $response['response'] ?? $response,
                'tokens' => $response['usage']['total_tokens'] ?? 0,
                'model' => $response['provider'] ?? 'microservice',
            ];
        } catch (\Exception $e) {
            Log::error('AI Chat error (microservice): ' . $e->getMessage());
            // Fallback to legacy if microservice fails
            return $this->fallbackChat($messages, $maxTokens);
        }
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

    /**
     * Fallback to legacy implementation
     */
    protected function fallbackChat(array $messages, int $maxTokens): array
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
     * OpenAI Chat (legacy)
     */
    protected function chatOpenAI(array $messages, int $maxTokens): array
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
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
     * Anthropic Chat (legacy)
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

        $response = \Illuminate\Support\Facades\Http::withHeaders([
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
}
