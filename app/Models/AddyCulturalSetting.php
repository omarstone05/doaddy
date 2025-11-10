<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddyCulturalSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'weekly_themes',
        'blocked_times',
        'timezone',
        'tone',
        'enable_predictions',
        'enable_proactive_suggestions',
        'max_daily_suggestions',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $casts = [
        'weekly_themes' => 'array',
        'blocked_times' => 'array',
        'enable_predictions' => 'boolean',
        'enable_proactive_suggestions' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get or create settings
     */
    public static function getOrCreate($organizationId): self
    {
        return self::firstOrCreate(
            ['organization_id' => $organizationId],
            [
                'tone' => 'professional',
                'timezone' => 'UTC',
                'max_daily_suggestions' => 5,
            ]
        );
    }

    /**
     * Check if in quiet hours
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now($this->timezone)->format('H:i');
        $start = $this->quiet_hours_start->format('H:i');
        $end = $this->quiet_hours_end->format('H:i');

        if ($start < $end) {
            return $now >= $start && $now <= $end;
        } else {
            // Handles overnight quiet hours
            return $now >= $start || $now <= $end;
        }
    }
}

