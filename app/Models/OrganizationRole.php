<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrganizationRole extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'level',
        'is_system',
    ];

    protected $casts = [
        'permissions' => 'array',
        'level' => 'integer',
        'is_system' => 'boolean',
    ];

    /**
     * Get users with this role in organizations
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user', 'role_id', 'user_id')
            ->withPivot('organization_id', 'is_active', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Check if role has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if role level is higher than another role
     */
    public function isHigherThan(OrganizationRole $role): bool
    {
        return $this->level > $role->level;
    }

    /**
     * Check if role can manage another role
     */
    public function canManage(OrganizationRole $role): bool
    {
        // Owner can manage all
        if ($this->slug === 'owner') {
            return true;
        }

        // Can only manage roles lower than your own
        return $this->level > $role->level;
    }

    /**
     * Get all permissions grouped by category
     */
    public function permissionsByCategory(): array
    {
        $grouped = [];

        foreach ($this->permissions ?? [] as $permission) {
            $parts = explode('.', $permission);
            $category = $parts[0] ?? 'other';
            $action = $parts[1] ?? $permission;

            $grouped[$category][] = $action;
        }

        return $grouped;
    }

    /**
     * Scope to get system roles
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get custom roles
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }
}
