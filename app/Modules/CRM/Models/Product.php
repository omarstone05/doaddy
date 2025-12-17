<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'crm_products';

    protected $fillable = [
        'organization_id',
        'product_code',
        'product_name',
        'description',
        'long_description',
        'product_category',
        'product_family',
        'unit_price',
        'cost_price',
        'currency',
        'is_recurring',
        'recurring_period',
        'track_inventory',
        'current_stock',
        'low_stock_threshold',
        'is_taxable',
        'tax_rate',
        'is_active',
        'available_from',
        'available_until',
        'image_url',
        'images',
        'specifications',
        'tags',
        'average_deal_size',
        'total_sold_quantity',
        'total_revenue',
    ];

    protected $casts = [
        'images' => 'array',
        'specifications' => 'array',
        'tags' => 'array',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'current_stock' => 'decimal:3',
        'low_stock_threshold' => 'decimal:3',
        'tax_rate' => 'decimal:2',
        'average_deal_size' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'available_from' => 'date',
        'available_until' => 'date',
        'is_recurring' => 'boolean',
        'track_inventory' => 'boolean',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];
}


