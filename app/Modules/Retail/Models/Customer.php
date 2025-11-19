<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'retail_customers';

    protected $fillable = [
        'organization_id',
        'name',
        'phone',
        'email',
        'address',
        'loyalty_points',
        'total_purchases',
        'lifetime_value',
        'last_purchase_date',
        'is_active',
        'custom_fields',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_purchases' => 'decimal:2',
        'lifetime_value' => 'decimal:2',
        'last_purchase_date' => 'date',
        'custom_fields' => 'array',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }
}

