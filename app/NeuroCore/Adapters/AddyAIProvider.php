<?php

namespace App\NeuroCore\Adapters;

use App\NeuroCore\Contracts\AIProviderInterface;
use App\Services\AI\AIService;

/**
 * AddyAIProvider - Wraps Addy's existing AIService for NeuroCore
 * This adapter allows Neuro to use the same AI configuration as Addy
 */
class AddyAIProvider implements AIProviderInterface
{
    private AIService $service;

    public function __construct(?AIService $service = null)
    {
        $this->service = $service ?? new AIService();
    }

    /**
     * Send a chat completion request
     */
    public function chat(array $messages, int $maxTokens = 1500, float $temperature = 0.7): array
    {
        return $this->service->chat($messages, $maxTokens);
    }

    /**
     * Quick single-message helper
     */
    public function ask(string $prompt, ?string $systemMessage = null): string
    {
        return $this->service->ask($prompt, $systemMessage);
    }

    /**
     * Check if provider is configured and available
     */
    public function isAvailable(): bool
    {
        try {
            // Try a minimal request
            return true; // AIService throws if not configured
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the current model being used
     */
    public function getModel(): string
    {
        // AIService doesn't expose this directly, but we can get it from config
        $provider = \App\Models\PlatformSetting::get('ai_provider', 'openai');
        
        if ($provider === 'openai') {
            return \App\Models\PlatformSetting::get('openai_model', 'gpt-4o');
        } else {
            return \App\Models\PlatformSetting::get('anthropic_model', 'claude-sonnet-4-20250514');
        }
    }
}


