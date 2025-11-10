<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddyActionPattern extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'action_type',
        'times_suggested',
        'times_confirmed',
        'times_rejected',
        'times_successful',
        'avg_rating',
        'successful_contexts',
        'failed_contexts',
    ];

    protected $casts = [
        'avg_rating' => 'decimal:2',
        'successful_contexts' => 'array',
        'failed_contexts' => 'array',
    ];

    /**
     * Get or create pattern
     */
    public static function getOrCreate($organizationId, $userId, string $actionType): self
    {
        return self::firstOrCreate([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'action_type' => $actionType,
        ]);
    }

    /**
     * Record suggestion
     */
    public function recordSuggestion(): void
    {
        $this->increment('times_suggested');
    }

    /**
     * Record confirmation
     */
    public function recordConfirmation(array $context = []): void
    {
        $this->increment('times_confirmed');
        
        if (!empty($context)) {
            $contexts = $this->successful_contexts ?? [];
            $contexts[] = array_merge($context, ['date' => now()->toDateString()]);
            $this->update(['successful_contexts' => array_slice($contexts, -10)]); // Keep last 10
        }
    }

    /**
     * Record rejection
     */
    public function recordRejection(array $context = []): void
    {
        $this->increment('times_rejected');
        
        if (!empty($context)) {
            $contexts = $this->failed_contexts ?? [];
            $contexts[] = array_merge($context, ['date' => now()->toDateString()]);
            $this->update(['failed_contexts' => array_slice($contexts, -10)]);
        }
    }

    /**
     * Record success
     */
    public function recordSuccess(int $rating = null): void
    {
        $this->increment('times_successful');
        
        if ($rating) {
            $currentAvg = $this->avg_rating ?? 0;
            $totalRatings = $this->times_successful - 1;
            $newAvg = (($currentAvg * $totalRatings) + $rating) / $this->times_successful;
            $this->update(['avg_rating' => $newAvg]);
        }
    }

    /**
     * Get confidence score (0-1)
     */
    public function getConfidence(): float
    {
        $total = $this->times_confirmed + $this->times_rejected;
        
        if ($total < 3) {
            return 0.5; // Not enough data
        }

        $confirmRate = $this->times_confirmed / $total;
        $successRate = $this->times_confirmed > 0 
            ? $this->times_successful / $this->times_confirmed 
            : 0;

        return ($confirmRate * 0.6) + ($successRate * 0.4);
    }

    /**
     * Should suggest this action?
     */
    public function shouldSuggest(): bool
    {
        return $this->getConfidence() >= 0.6;
    }
}

