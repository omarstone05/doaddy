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
            ->withPivot('role', 'role_id', 'is_active', 'joined_at')
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
            ->withPivot('role', 'role_id', 'is_active', 'joined_at')
            ->withTimestamps()
            ->orderBy('joined_at', 'desc');
    }

    /**
     * Get all roles available for this organization
     */
    public function roles()
    {
        return OrganizationRole::all();
    }

    /**
     * Assign a role to a user in this organization
     */
    public function assignRoleToUser(User $user, string $roleSlug): bool
    {
        $role = OrganizationRole::where('slug', $roleSlug)->first();
        
        if (!$role) {
            return false;
        }

        $this->members()->syncWithoutDetaching([
            $user->id => [
                'role_id' => $role->id,
                'role' => $role->slug, // Keep for backward compatibility
                'is_active' => true,
            ]
        ]);

        return true;
    }

    /**
     * Change a user's role in this organization
     */
    public function changeUserRole(User $user, string $roleSlug): bool
    {
        $role = OrganizationRole::where('slug', $roleSlug)->first();
        
        if (!$role) {
            return false;
        }

        $this->members()->updateExistingPivot($user->id, [
            'role_id' => $role->id,
            'role' => $role->slug, // Keep for backward compatibility
        ]);

        return true;
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
        $ownerRole = OrganizationRole::where('slug', 'owner')->first();
        
        if ($ownerRole) {
            return $this->members()->wherePivot('role_id', $ownerRole->id);
        }
        
        // Fallback to string role for backward compatibility
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

