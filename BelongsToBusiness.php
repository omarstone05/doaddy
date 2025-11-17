<?php

namespace App\Traits;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBusiness
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToBusiness()
    {
        // Automatically add business_id when creating
        static::creating(function ($model) {
            if (!$model->business_id && auth()->check()) {
                $model->business_id = auth()->user()->current_business_id;
            }
        });

        // Global scope to only show records from current business
        static::addGlobalScope('business', function (Builder $builder) {
            if (auth()->check() && auth()->user()->current_business_id) {
                $builder->where('business_id', auth()->user()->current_business_id);
            }
        });
    }

    /**
     * Get the business this model belongs to
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope query to specific business
     */
    public function scopeForBusiness(Builder $query, $businessId)
    {
        return $query->withoutGlobalScope('business')
            ->where('business_id', $businessId);
    }

    /**
     * Scope query to all businesses user has access to
     */
    public function scopeForUserBusinesses(Builder $query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        
        $businessIds = \App\Models\User::find($userId)
            ?->businesses()
            ->pluck('businesses.id')
            ->toArray() ?? [];

        return $query->withoutGlobalScope('business')
            ->whereIn('business_id', $businessIds);
    }
}
