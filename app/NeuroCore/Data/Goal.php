<?php

namespace App\NeuroCore\Data;

use Carbon\Carbon;

/**
 * Represents a user goal that Neuro tracks
 * Goals are things the user wants to achieve
 */
class Goal
{
    public string $id;
    public string $description;
    public string $status; // 'active', 'achieved', 'abandoned', 'paused'
    public ?string $category; // 'business', 'financial', 'personal', 'learning', etc.
    public ?Carbon $targetDate;
    public ?string $metric; // What metric tracks this goal
    public ?float $targetValue; // Target value for the metric
    public ?float $currentValue; // Current value
    public float $progress; // 0.0 to 1.0
    public array $milestones; // Sub-goals or checkpoints
    public array $context; // Why they have this goal, related info
    public ?string $systemOrigin; // Which system this goal was detected in
    public Carbon $createdAt;
    public Carbon $updatedAt;
    public array $relatedNeeds; // IDs of related needs
    public array $conversationRefs; // References to conversations where this was discussed

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? $this->generateId();
        $this->description = $data['description'] ?? '';
        $this->status = $data['status'] ?? 'active';
        $this->category = $data['category'] ?? null;
        $this->targetDate = isset($data['target_date']) ? Carbon::parse($data['target_date']) : null;
        $this->metric = $data['metric'] ?? null;
        $this->targetValue = $data['target_value'] ?? null;
        $this->currentValue = $data['current_value'] ?? null;
        $this->progress = $data['progress'] ?? 0.0;
        $this->milestones = $data['milestones'] ?? [];
        $this->context = $data['context'] ?? [];
        $this->systemOrigin = $data['system_origin'] ?? null;
        $this->createdAt = isset($data['created_at']) ? Carbon::parse($data['created_at']) : now();
        $this->updatedAt = isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : now();
        $this->relatedNeeds = $data['related_needs'] ?? [];
        $this->conversationRefs = $data['conversation_refs'] ?? [];
    }

    private function generateId(): string
    {
        return 'goal_' . bin2hex(random_bytes(8));
    }

    /**
     * Calculate progress based on current vs target value
     */
    public function calculateProgress(): float
    {
        if ($this->targetValue === null || $this->currentValue === null) {
            return $this->progress;
        }

        if ($this->targetValue == 0) {
            return $this->currentValue > 0 ? 1.0 : 0.0;
        }

        return min(1.0, max(0.0, $this->currentValue / $this->targetValue));
    }

    /**
     * Update progress and status
     */
    public function updateProgress(float $currentValue): void
    {
        $this->currentValue = $currentValue;
        $this->progress = $this->calculateProgress();
        $this->updatedAt = now();

        if ($this->progress >= 1.0) {
            $this->status = 'achieved';
        }
    }

    /**
     * Add a milestone
     */
    public function addMilestone(string $description, bool $completed = false): void
    {
        $this->milestones[] = [
            'id' => 'ms_' . bin2hex(random_bytes(4)),
            'description' => $description,
            'completed' => $completed,
            'completed_at' => $completed ? now()->toIso8601String() : null,
        ];
        $this->updatedAt = now();
    }

    /**
     * Add context about this goal
     */
    public function addContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
        $this->updatedAt = now();
    }

    /**
     * Reference a conversation where this goal was discussed
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
     * Get days until target date
     */
    public function getDaysRemaining(): ?int
    {
        if (!$this->targetDate) {
            return null;
        }

        return max(0, now()->diffInDays($this->targetDate, false));
    }

    /**
     * Check if goal is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->targetDate || $this->status === 'achieved') {
            return false;
        }

        return $this->targetDate->isPast();
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'status' => $this->status,
            'category' => $this->category,
            'target_date' => $this->targetDate?->toIso8601String(),
            'metric' => $this->metric,
            'target_value' => $this->targetValue,
            'current_value' => $this->currentValue,
            'progress' => $this->progress,
            'milestones' => $this->milestones,
            'context' => $this->context,
            'system_origin' => $this->systemOrigin,
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
            'related_needs' => $this->relatedNeeds,
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
     * Get a human-readable summary
     */
    public function getSummary(): string
    {
        $summary = $this->description;

        if ($this->targetDate) {
            $days = $this->getDaysRemaining();
            if ($days !== null) {
                $summary .= " (due in {$days} days)";
            }
        }

        if ($this->progress > 0) {
            $percentage = round($this->progress * 100);
            $summary .= " - {$percentage}% complete";
        }

        return $summary;
    }
}


