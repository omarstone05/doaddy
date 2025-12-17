<?php

namespace App\NeuroCore\Contracts;

/**
 * Interface for AI providers (OpenAI, Anthropic, etc.)
 * Allows Neuro to work with any AI backend
 */
interface AIProviderInterface
{
    /**
     * Send a chat completion request
     *
     * @param array $messages Array of messages [{role: string, content: string}]
     * @param int $maxTokens Maximum tokens in response
     * @param float $temperature Creativity level (0-1)
     * @return array ['content' => string, 'tokens' => int, 'model' => string]
     */
    public function chat(array $messages, int $maxTokens = 1500, float $temperature = 0.7): array;

    /**
     * Quick single-message helper
     *
     * @param string $prompt The user prompt
     * @param string|null $systemMessage Optional system message
     * @return string The response content
     */
    public function ask(string $prompt, ?string $systemMessage = null): string;

    /**
     * Check if provider is configured and available
     */
    public function isAvailable(): bool;

    /**
     * Get the current model being used
     */
    public function getModel(): string;
}


