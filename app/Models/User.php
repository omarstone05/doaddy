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
     * Get user's role in an organization (returns role string for backward compatibility)
     */
    public function getRoleInOrganization(string $organizationId): ?string
    {
        $pivot = $this->organizations()
            ->where('organizations.id', $organizationId)
            ->first()?->pivot;
        
        // Return role string if exists, otherwise get from role_id
        if ($pivot?->role) {
            return $pivot->role;
        }
        
        if ($pivot?->role_id) {
            $role = \App\Models\OrganizationRole::find($pivot->role_id);
            return $role?->slug;
        }
        
        return null;
    }

    /**
     * Get user's OrganizationRole in an organization
     */
    public function getOrganizationRole(string $organizationId): ?\App\Models\OrganizationRole
    {
        $pivot = $this->organizations()
            ->where('organizations.id', $organizationId)
            ->first()?->pivot;
        
        // Check role_id first, then fallback to role string
        if ($pivot) {
            if ($pivot->role_id) {
                return \App\Models\OrganizationRole::find($pivot->role_id);
            }
            
            // Fallback: try to find role by slug if role_id is null
            if ($pivot->role) {
                return \App\Models\OrganizationRole::where('slug', $pivot->role)->first();
            }
        }
        
        return null;
    }

    /**
     * Assign a role to user in an organization
     */
    public function assignRoleInOrganization(string $organizationId, string $roleSlug): bool
    {
        $role = \App\Models\OrganizationRole::where('slug', $roleSlug)->first();
        
        if (!$role) {
            return false;
        }

        $this->organizations()->updateExistingPivot($organizationId, [
            'role_id' => $role->id,
            'role' => $role->slug, // Keep for backward compatibility
        ]);

        return true;
    }

    /**
     * Check if user has permission in an organization
     */
    public function hasPermissionInOrganization(string $organizationId, string $permission): bool
    {
        $role = $this->getOrganizationRole($organizationId);
        
        if (!$role) {
            return false;
        }

        return $role->hasPermission($permission);
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

    /**
     * Find user by phone number with flexible format matching
     * Handles various phone number formats (with/without country code, leading 0, etc.)
     */
    public static function findByPhoneNumber(string $phoneNumber): ?self
    {
        $whatsappService = new \App\Services\WhatsAppService();
        $normalizedPhone = $whatsappService->formatPhoneNumberForApi($phoneNumber);
        
        // Remove non-numeric characters for comparison
        $cleanInput = preg_replace('/[^0-9]/', '', $phoneNumber);
        $cleanInputNoZero = ltrim($cleanInput, '0');
        
        return static::where(function($query) use ($normalizedPhone, $phoneNumber, $cleanInput, $cleanInputNoZero) {
            // Try normalized format (260973660337)
            $query->where('phone_number', $normalizedPhone)
                  // Try original input (0973660337)
                  ->orWhere('phone_number', $phoneNumber)
                  // Try without leading 0 (973660337)
                  ->orWhere('phone_number', $cleanInputNoZero)
                  // Try with + prefix
                  ->orWhere('phone_number', '+' . $normalizedPhone)
                  // Try with spaces (260 973 660 337)
                  ->orWhere('phone_number', preg_replace('/(\d{3})(\d{3})(\d{3})(\d{3})/', '$1 $2 $3 $4', $normalizedPhone))
                  // Try clean numeric input
                  ->orWhere('phone_number', $cleanInput);
        })->first();
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

    /**
     * Get all tasks assigned to this user
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'assigned_to_id');
    }

    /**
     * Get tasks currently being worked on by this user
     */
    public function activeTasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'assigned_to_id')
            ->where('status', 'in_progress')
            ->whereNotNull('started_working_at');
    }

    /**
     * Get all tasks assigned to this user in a specific organization
     */
    public function assignedTasksInOrganization(string $organizationId): HasMany
    {
        return $this->assignedTasks()
            ->where('organization_id', $organizationId);
    }

    /**
     * Get active tasks in a specific organization
     */
    public function activeTasksInOrganization(string $organizationId): HasMany
    {
        return $this->activeTasks()
            ->where('organization_id', $organizationId);
    }

    /**
     * Get task statistics for this user
     */
    public function getTaskStats(?string $organizationId = null): array
    {
        $query = $this->assignedTasks();
        
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return [
            'total' => (clone $query)->count(),
            'todo' => (clone $query)->where('status', 'todo')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'review' => (clone $query)->where('status', 'review')->count(),
            'done' => (clone $query)->where('status', 'done')->count(),
            'blocked' => (clone $query)->where('status', 'blocked')->count(),
            'active' => (clone $query)->where('status', 'in_progress')
                ->whereNotNull('started_working_at')
                ->count(),
        ];
    }

    /**
     * Many-to-many relationship with tasks (task assignments with privileges)
     */
    public function assignedTasksWithPrivileges(): BelongsToMany
    {
        return $this->belongsToMany(ProjectTask::class, 'task_user', 'user_id', 'task_id')
            ->withPivot([
                'assigned_by_id',
                'can_edit',
                'can_delete',
                'can_assign',
                'can_view_time',
                'can_manage_subtasks',
                'can_change_status',
                'can_change_priority',
                'assigned_at',
            ])
            ->withTimestamps()
            ->orderBy('assigned_at', 'desc');
    }

    /**
     * Get user's privileges for a specific task
     */
    public function getTaskPrivileges(string $taskId): ?array
    {
        $assignment = $this->assignedTasksWithPrivileges()
            ->where('project_tasks.id', $taskId)
            ->first();
        
        if (!$assignment) {
            return null;
        }

        return [
            'can_edit' => $assignment->pivot->can_edit,
            'can_delete' => $assignment->pivot->can_delete,
            'can_assign' => $assignment->pivot->can_assign,
            'can_view_time' => $assignment->pivot->can_view_time,
            'can_manage_subtasks' => $assignment->pivot->can_manage_subtasks,
            'can_change_status' => $assignment->pivot->can_change_status,
            'can_change_priority' => $assignment->pivot->can_change_priority,
            'assigned_at' => $assignment->pivot->assigned_at,
            'assigned_by_id' => $assignment->pivot->assigned_by_id,
        ];
    }
}
