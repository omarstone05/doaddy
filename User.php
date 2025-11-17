<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_business_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get all businesses this user belongs to
     */
    public function businesses()
    {
        return $this->belongsToMany(Business::class, 'business_user')
            ->withPivot(['role_id', 'is_active', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get active businesses only
     */
    public function activeBusinesses()
    {
        return $this->businesses()
            ->wherePivot('is_active', true)
            ->where('businesses.is_active', true);
    }

    /**
     * Get current active business
     */
    public function currentBusiness()
    {
        return $this->belongsTo(Business::class, 'current_business_id');
    }

    /**
     * Switch to a different business
     */
    public function switchBusiness(Business $business)
    {
        // Check if user has access to this business
        if (!$this->businesses->contains($business->id)) {
            throw new \Exception('You do not have access to this business.');
        }

        $this->update(['current_business_id' => $business->id]);

        return $this->fresh();
    }

    /**
     * Get user's role in current business
     */
    public function currentRole(): ?Role
    {
        if (!$this->current_business_id) {
            return null;
        }

        $pivot = $this->businesses()
            ->where('business_id', $this->current_business_id)
            ->first()
            ?->pivot;

        return $pivot ? Role::find($pivot->role_id) : null;
    }

    /**
     * Get user's role in specific business
     */
    public function roleIn(Business $business): ?Role
    {
        $pivot = $this->businesses()
            ->where('business_id', $business->id)
            ->first()
            ?->pivot;

        return $pivot ? Role::find($pivot->role_id) : null;
    }

    /**
     * Check if user has specific role in current business
     */
    public function hasRole(string $roleSlug): bool
    {
        $role = $this->currentRole();
        return $role && $role->slug === $roleSlug;
    }

    /**
     * Check if user is owner of current business
     */
    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }

    /**
     * Check if user is admin of current business
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user has permission in current business
     */
    public function can($permission, $arguments = []): bool
    {
        // Check parent (Laravel's authorization)
        if (parent::can($permission, $arguments)) {
            return true;
        }

        // Check business permission
        $role = $this->currentRole();
        
        if (!$role) {
            return false;
        }

        return in_array($permission, $role->permissions ?? []);
    }

    /**
     * Get all businesses where user is owner
     */
    public function ownedBusinesses()
    {
        $ownerRole = Role::where('slug', 'owner')->first();
        
        return $this->businesses()
            ->wherePivot('role_id', $ownerRole?->id);
    }

    /**
     * Create a new business and make user the owner
     */
    public function createBusiness(array $data)
    {
        $business = Business::create($data);
        
        $ownerRole = Role::where('slug', 'owner')->firstOrFail();
        
        $this->businesses()->attach($business->id, [
            'role_id' => $ownerRole->id,
            'joined_at' => now(),
            'is_active' => true,
        ]);

        // Set as current business if user has none
        if (!$this->current_business_id) {
            $this->update(['current_business_id' => $business->id]);
        }

        return $business;
    }

    /**
     * Get businesses grouped by role
     */
    public function businessesByRole()
    {
        return $this->businesses()
            ->get()
            ->groupBy(function ($business) {
                return Role::find($business->pivot->role_id)?->name ?? 'Unknown';
            });
    }

    /**
     * Get permissions in current business
     */
    public function permissions(): array
    {
        $role = $this->currentRole();
        return $role ? $role->permissions : [];
    }
}
