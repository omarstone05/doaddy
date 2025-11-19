<?php

namespace App\Modules\Retail\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasUuid;

    protected $table = 'retail_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'variant_id',
        'description',
        'quantity_ordered',
        'quantity_received',
        'quantity_pending',
        'unit_cost',
        'tax_rate',
        'line_total',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'quantity_pending' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

