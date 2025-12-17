<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddyInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'addy_state_id',
        'type',
        'category',
        'title',
        'description',
        'priority',
        'is_actionable',
        'suggested_actions',
        'action_url',
        'status',
        'dismissed_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'priority' => 'decimal:2',
        'is_actionable' => 'boolean',
        'suggested_actions' => 'array',
        'dismissed_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(AddyState::class, 'addy_state_id');
    }

    public static function active($organizationId)
    {
        return self::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('priority')
            ->orderByDesc('created_at');
    }

    public function dismiss(): void
    {
        $this->update([
            'status' => 'dismissed',
            'dismissed_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}

