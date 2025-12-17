<?php

namespace App\NeuroCore\Context;

use App\NeuroCore\Contracts\StorageInterface;
use App\NeuroCore\Data\NeuroResponse;
use App\NeuroCore\Data\NeuroUnderstanding;
use Carbon\Carbon;

/**
 * ConversationMemory - Maintains conversation history with context
 * Stores both messages and what was learned from each exchange
 */
class ConversationMemory
{
    private string $userId;
    private string $systemContext;
    private StorageInterface $storage;
    private string $currentConversationId;

    // In-memory cache of current conversation
    private array $messages = [];

    public function __construct(string $userId, string $systemContext, StorageInterface $storage)
    {
        $this->userId = $userId;
        $this->systemContext = $systemContext;
        $this->storage = $storage;
        $this->currentConversationId = $this->getOrCreateConversationId();
        $this->loadCurrentConversation();
    }

    /**
     * Get or create a conversation ID for today's session
     */
    private function getOrCreateConversationId(): string
    {
        $key = "neuro:conversation_id:{$this->userId}:{$this->systemContext}";
        $stored = $this->storage->get($key);

        if ($stored) {
            $data = is_array($stored) ? $stored : ['id' => $stored, 'created_at' => now()->toIso8601String()];
            // Check if conversation is still fresh (within 4 hours)
            $createdAt = Carbon::parse($data['created_at']);
            if ($createdAt->diffInHours(now()) < 4) {
                return $data['id'];
            }
        }

        // Create new conversation
        $newId = 'conv_' . bin2hex(random_bytes(8));
        $this->storage->set($key, [
            'id' => $newId,
            'created_at' => now()->toIso8601String(),
        ], 60 * 60 * 24); // TTL: 24 hours

        return $newId;
    }

    /**
     * Load current conversation from storage
     */
    private function loadCurrentConversation(): void
    {
        $key = "neuro:messages:{$this->userId}:{$this->currentConversationId}";
        $stored = $this->storage->get($key);
        $this->messages = $stored ?? [];
    }

    /**
     * Store a message exchange
     */
    public function store(
        string $userMessage,
        NeuroResponse $response,
        ?NeuroUnderstanding $understanding = null
    ): string {
        $messageId = 'msg_' . bin2hex(random_bytes(6));

        $exchange = [
            'id' => $messageId,
            'conversation_id' => $this->currentConversationId,
            'timestamp' => now()->toIso8601String(),
            'user' => [
                'message' => $userMessage,
            ],
            'assistant' => [
                'message' => $response->message,
                'assistance_type' => $response->assistanceType,
                'has_guidance' => $response->hasGuidance(),
            ],
            'understanding' => $understanding ? [
                'intent' => $understanding->intent,
                'sentiment' => $understanding->sentiment,
                'detected_goals' => count($understanding->detectedGoals),
                'detected_needs' => count($understanding->detectedNeeds),
                'topics' => $understanding->topics,
            ] : null,
            'context_update' => $response->contextUpdate,
        ];

        $this->messages[] = $exchange;

        // Save to storage
        $key = "neuro:messages:{$this->userId}:{$this->currentConversationId}";
        $this->storage->set($key, $this->messages, 60 * 60 * 24 * 7); // TTL: 7 days

        // Also save to history for long-term storage
        $this->saveToHistory($exchange);

        return $messageId;
    }

    /**
     * Save exchange to long-term history
     */
    private function saveToHistory(array $exchange): void
    {
        $key = "neuro:history:{$this->userId}:{$this->systemContext}";
        $history = $this->storage->get($key) ?? [];
        
        // Keep last 100 exchanges
        $history[] = $exchange;
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        $this->storage->set($key, $history);
    }

    /**
     * Get recent messages from current conversation
     */
    public function getRecentHistory(int $limit = 10): array
    {
        $messages = array_slice($this->messages, -$limit);

        // Format for AI context
        $formatted = [];
        foreach ($messages as $exchange) {
            $formatted[] = [
                'role' => 'user',
                'content' => $exchange['user']['message'],
            ];
            $formatted[] = [
                'role' => 'assistant',
                'content' => $exchange['assistant']['message'],
            ];
        }

        return $formatted;
    }

    /**
     * Get full conversation history
     */
    public function getHistory(int $limit = 50): array
    {
        return array_slice($this->messages, -$limit);
    }

    /**
     * Get historical conversations (across sessions)
     */
    public function getHistoricalExchanges(int $limit = 20): array
    {
        $key = "neuro:history:{$this->userId}:{$this->systemContext}";
        $history = $this->storage->get($key) ?? [];
        return array_slice($history, -$limit);
    }

    /**
     * Search history for relevant context
     */
    public function searchHistory(string $query, int $limit = 5): array
    {
        $key = "neuro:history:{$this->userId}:{$this->systemContext}";
        $history = $this->storage->get($key) ?? [];

        $query = strtolower($query);
        $matches = [];

        foreach ($history as $exchange) {
            $userMsg = strtolower($exchange['user']['message'] ?? '');
            $assistantMsg = strtolower($exchange['assistant']['message'] ?? '');
            $topics = $exchange['understanding']['topics'] ?? [];

            // Simple relevance scoring
            $score = 0;
            if (stripos($userMsg, $query) !== false) {
                $score += 2;
            }
            if (stripos($assistantMsg, $query) !== false) {
                $score += 1;
            }
            foreach ($topics as $topic) {
                if (stripos($topic, $query) !== false) {
                    $score += 1;
                }
            }

            if ($score > 0) {
                $matches[] = ['exchange' => $exchange, 'score' => $score];
            }
        }

        // Sort by score
        usort($matches, fn($a, $b) => $b['score'] - $a['score']);

        return array_map(
            fn($m) => $m['exchange'],
            array_slice($matches, 0, $limit)
        );
    }

    /**
     * Get context about what was discussed related to a goal
     */
    public function getGoalContext(string $goalId): array
    {
        $key = "neuro:history:{$this->userId}:{$this->systemContext}";
        $history = $this->storage->get($key) ?? [];

        return array_filter($history, function ($exchange) use ($goalId) {
            // Check if this exchange relates to the goal
            $contextUpdate = $exchange['context_update'] ?? null;
            if ($contextUpdate && ($contextUpdate['details']['goal_id'] ?? null) === $goalId) {
                return true;
            }
            return false;
        });
    }

    /**
     * Get current conversation ID
     */
    public function getConversationId(): string
    {
        return $this->currentConversationId;
    }

    /**
     * Start a new conversation (force new session)
     */
    public function startNewConversation(): string
    {
        $newId = 'conv_' . bin2hex(random_bytes(8));
        
        $key = "neuro:conversation_id:{$this->userId}:{$this->systemContext}";
        $this->storage->set($key, [
            'id' => $newId,
            'created_at' => now()->toIso8601String(),
        ], 60 * 60 * 24);

        $this->currentConversationId = $newId;
        $this->messages = [];

        return $newId;
    }

    /**
     * Get conversation summary (for AI context)
     */
    public function getConversationSummary(): string
    {
        if (empty($this->messages)) {
            return "This is the start of the conversation.";
        }

        $count = count($this->messages);
        $topics = [];
        $intents = [];

        foreach ($this->messages as $exchange) {
            if (!empty($exchange['understanding']['topics'])) {
                $topics = array_merge($topics, $exchange['understanding']['topics']);
            }
            if (!empty($exchange['understanding']['intent'])) {
                $intents[] = $exchange['understanding']['intent'];
            }
        }

        $topics = array_unique($topics);
        $intentCounts = array_count_values($intents);
        arsort($intentCounts);

        $summary = "Conversation has {$count} exchange(s).";
        if (!empty($topics)) {
            $summary .= " Topics discussed: " . implode(', ', array_slice($topics, 0, 5));
        }
        if (!empty($intentCounts)) {
            $mainIntent = array_key_first($intentCounts);
            $summary .= ". User has mainly been: {$mainIntent}";
        }

        return $summary;
    }

    /**
     * Get the last exchange
     */
    public function getLastExchange(): ?array
    {
        if (empty($this->messages)) {
            return null;
        }
        return end($this->messages);
    }
}


