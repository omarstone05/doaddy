<?php

namespace App\Modules\Retail\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasUuid;

    protected $table = 'retail_sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
        'variant_id',
        'product_name',
        'sku',
        'quantity',
        'unit_of_measure',
        'unit_cost',
        'unit_price',
        'discount_per_item',
        'tax_rate',
        'line_total',
        'line_cost',
        'line_profit',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_per_item' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
        'line_cost' => 'decimal:2',
        'line_profit' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
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
     * Calculate line totals
     */
    public function calculateTotals(): void
    {
        $this->line_cost = $this->unit_cost * $this->quantity;
        $lineSubtotal = ($this->unit_price * $this->quantity) - $this->discount_per_item;
        $lineTax = $lineSubtotal * ($this->tax_rate / 100);
        $this->line_total = $lineSubtotal + $lineTax;
        $this->line_profit = $this->line_total - $this->line_cost;
        $this->save();
    }
}
