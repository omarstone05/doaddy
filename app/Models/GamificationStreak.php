<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamificationStreak extends Model
{
    use HasUuids;

    protected $table = 'gamification_streaks';

    protected $fillable = [
        'user_id',
        'organization_id',
        'streak_type',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'weekend_pause_enabled',
        'streak_freezes_remaining',
    ];

    protected $casts = [
        'last_activity_date' => 'date',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'weekend_pause_enabled' => 'boolean',
        'streak_freezes_remaining' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

