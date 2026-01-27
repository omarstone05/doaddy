<?php

namespace App\Services\AI;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Penda\AIClient\PendaAIClient;
use Penda\JWT\PendaJWT;

class AIService
{
    protected PendaAIClient $client;
    protected PendaJWT $jwt;
    protected bool $useMicroservice;

    public function __construct()
    {
        $this->jwt = app(PendaJWT::class);
        $this->client = app('penda.ai');
        $this->useMicroservice = PlatformSetting::get('ai_service_enabled', true);
        
        // Set source app
        $this->client->setSourceApp('addy');
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
        try {
            $token = $this->getJwtToken();
            $this->client->setToken($token);
            
            // Convert messages array to single message for Penda Cloud API
            $userMessage = '';
            $systemPrompt = null;
            
            foreach ($messages as $msg) {
                if ($msg['role'] === 'user') {
                    $userMessage = $msg['content'];
                } elseif ($msg['role'] === 'system') {
                    $systemPrompt = $msg['content'];
                }
            }
            
            if (empty($userMessage)) {
                // Extract from last user message
                $userMessages = array_filter($messages, fn($m) => $m['role'] === 'user');
                $lastUserMsg = end($userMessages);
                $userMessage = $lastUserMsg['content'] ?? '';
            }
            
            $response = $this->client->chat($userMessage, $systemPrompt ? ['system_prompt' => $systemPrompt] : []);
            
            return [
                'content' => $response['response'] ?? '',
                'tokens' => $response['usage']['total_tokens'] ?? 0,
                'model' => $response['model'] ?? 'penda-ai',
                'provider' => $response['provider'] ?? 'penda-cloud',
            ];
        } catch (\Exception $e) {
            Log::error('AI Chat error: ' . $e->getMessage());
            throw $e;
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
     * Business assistant query
     */
    public function businessQuery(string $query, array $businessContext = []): array
    {
        try {
            $token = $this->getJwtToken();
            $this->client->setToken($token);
            
            return $this->client->businessAssistant($query, $businessContext);
        } catch (\Exception $e) {
            Log::error('Business assistant error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test the AI connection
     */
    public function testConnection(): array
    {
        try {
            // First check health endpoint (no auth required)
            $health = $this->client->health();
            
            if (($health['status'] ?? '') !== 'ok') {
                return [
                    'success' => false,
                    'provider' => 'penda-cloud',
                    'error' => 'AI service not available',
                    'health' => $health,
                ];
            }

            // Then test authenticated request
            $token = $this->getJwtToken();
            $this->client->setToken($token);
            
            $response = $this->client->chat('Say "Connection successful from Addy!" and nothing else.');
            
            return [
                'success' => true,
                'provider' => $response['provider'] ?? 'penda-cloud',
                'model' => $response['model'] ?? 'unknown',
                'response' => $response['response'] ?? '',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'provider' => 'penda-cloud',
                'error' => $e->getMessage(),
            ];
        }
    }
}
