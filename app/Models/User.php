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
        'phone_number',
        'organization_id',
        'is_super_admin',
        'is_active',
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
            'is_active' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    /**
     * Many-to-many relationship with organizations
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot('role', 'is_active', 'joined_at')
            ->withTimestamps()
            ->orderBy('joined_at', 'desc');
    }

    /**
     * Get the current organization (for backward compatibility)
     * Uses session, organization_id, or first organization
     */
    public function getOrganizationAttribute(): ?Organization
    {
        // Try to get from session first
        $currentOrgId = session('current_organization_id');
        if ($currentOrgId) {
            $org = $this->organizations()->where('organizations.id', $currentOrgId)->first();
            if ($org) {
                return $org;
            }
        }
        
        // Try organization_id (for backward compatibility)
        if ($this->attributes['organization_id'] ?? null) {
            $org = $this->organizations()->where('organizations.id', $this->attributes['organization_id'])->first();
            if ($org) {
                return $org;
            }
        }
        
        // Fallback to first organization
        return $this->organizations()->first();
    }

    /**
     * Get the current organization ID
     */
    public function getCurrentOrganizationIdAttribute(): ?string
    {
        return session('current_organization_id') 
            ?? ($this->attributes['organization_id'] ?? null)
            ?? $this->organizations()->first()?->id;
    }

    /**
     * Check if user belongs to an organization
     */
    public function belongsToOrganization(string $organizationId): bool
    {
        return $this->organizations()->where('organizations.id', $organizationId)->exists();
    }

    /**
     * Get user's role in an organization
     */
    public function getRoleInOrganization(string $organizationId): ?string
    {
        $pivot = $this->organizations()
            ->where('organizations.id', $organizationId)
            ->first()?->pivot;
        
        return $pivot?->role;
    }

    /**
     * Check if user is owner of an organization
     */
    public function isOwnerOf(string $organizationId): bool
    {
        return $this->getRoleInOrganization($organizationId) === 'owner';
    }

    /**
     * Get organization_id (for backward compatibility)
     * Returns current organization ID
     */
    public function getOrganizationIdAttribute(): ?string
    {
        return $this->current_organization_id;
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

    public function metrics(): HasMany
    {
        return $this->hasMany(UserMetric::class);
    }
}
