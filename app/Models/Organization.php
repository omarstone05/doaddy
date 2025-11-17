<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'business_type',
        'industry',
        'tone_preference',
        'currency',
        'timezone',
        'logo',
        'settings',
        'status',
        'trial_ends_at',
        'suspended_at',
        'suspension_reason',
        'billing_plan',
        'mrr',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'mrr' => 'decimal:2',
        ];
    }

    /**
     * Many-to-many relationship with users (via pivot table)
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot('role', 'is_active', 'joined_at')
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->orderBy('joined_at', 'desc');
    }

    /**
     * All users (including inactive) via pivot table
     */
    public function allMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot('role', 'is_active', 'joined_at')
            ->withTimestamps()
            ->orderBy('joined_at', 'desc');
    }

    /**
     * Legacy users relationship (for backward compatibility)
     * @deprecated Use members() instead
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get owners of this organization
     */
    public function owners(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'owner');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            })
            ->latest()
            ->first();
    }
}

