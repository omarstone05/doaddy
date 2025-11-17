<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'business_type',
        'email',
        'phone',
        'address',
        'tax_number',
        'currency',
        'timezone',
        'settings',
        'is_active',
        'subscription_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'subscription_ends_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (empty($business->slug)) {
                $business->slug = Str::slug($business->name);
            }
        });
    }

    /**
     * Get all users that belong to this business
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'business_user')
            ->withPivot(['role_id', 'is_active', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get owner of the business
     */
    public function owner()
    {
        return $this->belongsToMany(User::class, 'business_user')
            ->wherePivot('role_id', Role::where('slug', 'owner')->first()?->id)
            ->first();
    }

    /**
     * Get active users only
     */
    public function activeUsers()
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Get transactions for this business
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get customers for this business
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get products for this business
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get invoices for this business
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if business is active
     */
    public function isActive(): bool
    {
        return $this->is_active && 
               ($this->subscription_ends_at === null || 
                $this->subscription_ends_at->isFuture());
    }

    /**
     * Get user's role in this business
     */
    public function getUserRole(User $user): ?Role
    {
        $pivot = $this->users()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot;

        return $pivot ? Role::find($pivot->role_id) : null;
    }

    /**
     * Check if user has specific role in this business
     */
    public function userHasRole(User $user, string $roleSlug): bool
    {
        $role = $this->getUserRole($user);
        return $role && $role->slug === $roleSlug;
    }

    /**
     * Check if user has permission in this business
     */
    public function userCan(User $user, string $permission): bool
    {
        $role = $this->getUserRole($user);
        
        if (!$role) {
            return false;
        }

        return in_array($permission, $role->permissions ?? []);
    }

    /**
     * Add user to business with role
     */
    public function addUser(User $user, string $roleSlug, array $extra = [])
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return $this->users()->attach($user->id, array_merge([
            'role_id' => $role->id,
            'joined_at' => now(),
        ], $extra));
    }

    /**
     * Remove user from business
     */
    public function removeUser(User $user)
    {
        return $this->users()->detach($user->id);
    }

    /**
     * Change user's role in business
     */
    public function changeUserRole(User $user, string $roleSlug)
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return $this->users()->updateExistingPivot($user->id, [
            'role_id' => $role->id,
        ]);
    }
}
