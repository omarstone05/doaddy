<?php

namespace App\Modules\Retail\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasUuid;

    protected $table = 'retail_stock_adjustment_items';

    protected $fillable = [
        'adjustment_id',
        'product_id',
        'variant_id',
        'expected_quantity',
        'actual_quantity',
        'difference',
        'unit_cost',
        'total_value',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'difference' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Calculate difference and total value
     */
    public function calculateTotals(): void
    {
        $this->difference = $this->actual_quantity - ($this->expected_quantity ?? 0);
        $this->total_value = abs($this->difference) * $this->unit_cost;
        $this->save();
    }
}

