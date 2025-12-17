<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'product_id',
        'order',
        'name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'tax_percentage',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(GoodsAndService::class, 'product_id');
    }

    // Methods
    public function calculateTotal(): void
    {
        $lineTotal = $this->quantity * $this->unit_price;
        
        if ($this->discount_percentage > 0) {
            $this->discount_amount = ($lineTotal * $this->discount_percentage) / 100;
        }
        
        $amountAfterDiscount = $lineTotal - $this->discount_amount;
        
        if ($this->tax_percentage > 0) {
            $this->tax_amount = ($amountAfterDiscount * $this->tax_percentage) / 100;
        }
        
        $this->total = $amountAfterDiscount + $this->tax_amount;
    }
}
