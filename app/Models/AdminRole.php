<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminRole extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_role_user')
            ->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === 'super_admin';
    }
}

