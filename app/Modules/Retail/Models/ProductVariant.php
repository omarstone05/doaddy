<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'retail_product_variants';

    protected $fillable = [
        'product_id',
        'organization_id',
        'sku',
        'barcode',
        'name',
        'option1_name',
        'option1_value',
        'option2_name',
        'option2_value',
        'option3_name',
        'option3_value',
        'current_stock',
        'minimum_stock',
        'cost_price',
        'selling_price',
        'price_difference',
        'weight',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'price_difference' => 'decimal:2',
        'weight' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

