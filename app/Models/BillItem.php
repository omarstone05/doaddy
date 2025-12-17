<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $fillable = [
        'bill_id',
        'product_id',
        'order',
        'name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'total',
        'account_code',
        'cost_center',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->calculateTotal();
        });

        static::saved(function ($item) {
            if ($item->bill && method_exists($item->bill, 'calculateTotals')) {
                $item->bill->calculateTotals();
            }
        });

        static::deleted(function ($item) {
            if ($item->bill && method_exists($item->bill, 'calculateTotals')) {
                $item->bill->calculateTotals();
            }
        });
    }

    // Relationships
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function product(): BelongsTo
    {
        // Check if Product model exists, otherwise use GoodsAndService
        if (class_exists(\App\Models\Product::class)) {
            return $this->belongsTo(\App\Models\Product::class);
        }
        return $this->belongsTo(GoodsAndService::class, 'product_id');
    }

    // Methods
    public function calculateTotal(): void
    {
        $lineTotal = $this->quantity * $this->unit_price;
        $this->total = $lineTotal - $this->discount_amount + $this->tax_amount;
    }
}
