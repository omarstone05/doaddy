<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddyUserPattern extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'weekly_rhythm',
        'peak_hours',
        'section_preferences',
        'avg_response_time',
        'action_patterns',
        'dismissed_insight_types',
        'work_style',
        'adhd_mode',
        'preferred_task_chunk_size',
    ];

    protected $casts = [
        'weekly_rhythm' => 'array',
        'peak_hours' => 'array',
        'section_preferences' => 'array',
        'action_patterns' => 'array',
        'dismissed_insight_types' => 'array',
        'adhd_mode' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create pattern for user
     */
    public static function getOrCreate($organizationId, $userId): self
    {
        return self::firstOrCreate(
            [
                'organization_id' => $organizationId,
                'user_id' => $userId,
            ],
            [
                'weekly_rhythm' => self::getDefaultWeeklyRhythm(),
                'peak_hours' => [9, 10, 11, 14, 15, 16], // Default working hours
                'work_style' => 'balanced',
            ]
        );
    }

    /**
     * Default weekly rhythm
     */
    protected static function getDefaultWeeklyRhythm(): array
    {
        return [
            'monday' => ['theme' => 'Deep Work', 'focus' => 'planning'],
            'tuesday' => ['theme' => 'Build', 'focus' => 'execution'],
            'wednesday' => ['theme' => 'Collaborate', 'focus' => 'meetings'],
            'thursday' => ['theme' => 'Review', 'focus' => 'analysis'],
            'friday' => ['theme' => 'Creative', 'focus' => 'innovation'],
            'saturday' => ['theme' => 'Rest', 'focus' => 'personal'],
            'sunday' => ['theme' => 'Reflect', 'focus' => 'planning'],
        ];
    }

    /**
     * Get today's theme
     */
    public function getTodayTheme(): array
    {
        $day = strtolower(now()->format('l'));
        return $this->weekly_rhythm[$day] ?? ['theme' => 'Work', 'focus' => 'general'];
    }

    /**
     * Check if in peak hours
     */
    public function isInPeakHours(): bool
    {
        $currentHour = now()->hour;
        return in_array($currentHour, $this->peak_hours ?? []);
    }

    /**
     * Record section visit
     */
    public function recordSectionVisit(string $section): void
    {
        $preferences = $this->section_preferences ?? [];
        $preferences[$section] = ($preferences[$section] ?? 0) + 1;
        
        $this->update(['section_preferences' => $preferences]);
    }

    /**
     * Record insight action
     */
    public function recordInsightAction(string $insightType, string $action): void
    {
        $patterns = $this->action_patterns ?? [];
        $key = "{$insightType}_{$action}";
        $patterns[$key] = ($patterns[$key] ?? 0) + 1;
        
        $this->update(['action_patterns' => $patterns]);
    }
}

