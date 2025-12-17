<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AddyState extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'focus_area',
        'urgency',
        'context',
        'mood',
        'perception_data',
        'priorities',
        'last_thought_cycle',
    ];

    protected $casts = [
        'urgency' => 'decimal:2',
        'perception_data' => 'array',
        'priorities' => 'array',
        'last_thought_cycle' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(AddyInsight::class);
    }

    public static function current($organizationId): ?self
    {
        return self::where('organization_id', $organizationId)
            ->latest('last_thought_cycle')
            ->first();
    }

    public function needsThoughtCycle(): bool
    {
        if (!$this->last_thought_cycle) {
            return true;
        }

        return $this->last_thought_cycle->diffInHours(now()) >= 24;
    }
}

