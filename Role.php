<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'level',
    ];

    protected $casts = [
        'permissions' => 'array',
        'level' => 'integer',
    ];

    /**
     * Get users with this role
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'business_user')
            ->withPivot(['business_id', 'is_active'])
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
    public function isHigherThan(Role $role): bool
    {
        return $this->level > $role->level;
    }

    /**
     * Check if role can manage another role
     */
    public function canManage(Role $role): bool
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
}
