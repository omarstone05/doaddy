<?php

namespace App\NeuroCore\Data;

use Carbon\Carbon;

/**
 * Represents a user need that Neuro tracks
 * Needs are things the user requires to accomplish their work/goals
 */
class Need
{
    public string $id;
    public string $description;
    public string $category; // 'efficiency', 'capability', 'knowledge', 'resource', 'support'
    public int $priority; // 1-5, 5 being highest
    public string $status; // 'active', 'addressed', 'deferred'
    public array $relatedGoals; // Goal IDs this need supports
    public array $context; // Additional context about this need
    public ?string $systemOrigin;
    public int $mentionCount; // How many times user has mentioned this need
    public Carbon $firstMentioned;
    public Carbon $lastMentioned;
    public array $conversationRefs;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? $this->generateId();
        $this->description = $data['description'] ?? '';
        $this->category = $data['category'] ?? 'general';
        $this->priority = $data['priority'] ?? 3;
        $this->status = $data['status'] ?? 'active';
        $this->relatedGoals = $data['related_goals'] ?? [];
        $this->context = $data['context'] ?? [];
        $this->systemOrigin = $data['system_origin'] ?? null;
        $this->mentionCount = $data['mention_count'] ?? 1;
        $this->firstMentioned = isset($data['first_mentioned']) 
            ? Carbon::parse($data['first_mentioned']) 
            : now();
        $this->lastMentioned = isset($data['last_mentioned']) 
            ? Carbon::parse($data['last_mentioned']) 
            : now();
        $this->conversationRefs = $data['conversation_refs'] ?? [];
    }

    private function generateId(): string
    {
        return 'need_' . bin2hex(random_bytes(8));
    }

    /**
     * Record another mention of this need
     */
    public function recordMention(): void
    {
        $this->mentionCount++;
        $this->lastMentioned = now();
        
        // Increase priority if frequently mentioned
        if ($this->mentionCount >= 5 && $this->priority < 5) {
            $this->priority = min(5, $this->priority + 1);
        }
    }

    /**
     * Link to a goal
     */
    public function linkToGoal(string $goalId): void
    {
        if (!in_array($goalId, $this->relatedGoals)) {
            $this->relatedGoals[] = $goalId;
        }
    }

    /**
     * Add context
     */
    public function addContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * Reference a conversation
     */
    public function addConversationRef(string $conversationId, string $messageId): void
    {
        $this->conversationRefs[] = [
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Check if this is a recurring/persistent need
     */
    public function isRecurring(): bool
    {
        return $this->mentionCount >= 3;
    }

    /**
     * Get urgency based on priority and recurrence
     */
    public function getUrgency(): float
    {
        $base = $this->priority / 5.0;
        $recurrenceBoost = min(0.2, $this->mentionCount * 0.02);
        return min(1.0, $base + $recurrenceBoost);
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'related_goals' => $this->relatedGoals,
            'context' => $this->context,
            'system_origin' => $this->systemOrigin,
            'mention_count' => $this->mentionCount,
            'first_mentioned' => $this->firstMentioned->toIso8601String(),
            'last_mentioned' => $this->lastMentioned->toIso8601String(),
            'conversation_refs' => $this->conversationRefs,
        ];
    }

    /**
     * Create from stored array
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Get category label
     */
    public static function getCategoryLabel(string $category): string
    {
        return match($category) {
            'efficiency' => 'Efficiency & Productivity',
            'capability' => 'Skills & Capabilities',
            'knowledge' => 'Information & Knowledge',
            'resource' => 'Resources & Tools',
            'support' => 'Help & Support',
            default => 'General',
        };
    }

    /**
     * Get a human-readable summary
     */
    public function getSummary(): string
    {
        $priorityLabel = match($this->priority) {
            5 => 'Critical',
            4 => 'High',
            3 => 'Medium',
            2 => 'Low',
            default => 'Minor',
        };

        return "[{$priorityLabel}] {$this->description}";
    }
}


