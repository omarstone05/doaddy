<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuid, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'is_super_admin',
        'admin_notes',
        'last_active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function teamMember(): HasOne
    {
        return $this->hasOne(TeamMember::class);
    }

    // Admin methods
    public function adminRoles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_user')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->adminRoles()->exists() || $this->is_super_admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->adminRoles()->where('slug', 'super_admin')->exists() || $this->is_super_admin;
    }

    public function hasAdminPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->adminRoles()
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->contains($permission);
    }

    public function canImpersonate(): bool
    {
        return $this->hasAdminPermission('impersonate_users');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }
}
