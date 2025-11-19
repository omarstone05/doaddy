<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'retail_suppliers';

    protected $fillable = [
        'organization_id',
        'name',
        'company_name',
        'supplier_code',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'payment_terms',
        'tax_number',
        'bank_details',
        'total_purchased',
        'total_orders',
        'average_delivery_days',
        'rating',
        'is_active',
        'tags',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_purchased' => 'decimal:2',
        'bank_details' => 'array',
        'tags' => 'array',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }
}

