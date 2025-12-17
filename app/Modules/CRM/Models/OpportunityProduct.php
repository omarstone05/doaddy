<?php

namespace App\Modules\CRM\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityProduct extends Model
{
    use HasUuid;

    protected $table = 'crm_opportunity_products';

    protected $fillable = [
        'opportunity_id',
        'product_id',
        'product_name',
        'description',
        'product_code',
        'quantity',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'total',
        'is_recurring',
        'recurring_period',
        'recurring_months',
        'total_contract_value',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'total_contract_value' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}


