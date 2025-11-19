<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'retail_purchase_orders';

    protected $fillable = [
        'organization_id',
        'location_id',
        'po_number',
        'supplier_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'received_date',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'discount',
        'total_amount',
        'currency',
        'payment_status',
        'amount_paid',
        'payment_terms',
        'created_by',
        'approved_by',
        'received_by',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'received_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'attachments' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('line_total');
        $this->tax_amount = $this->items->sum(function ($item) {
            return $item->line_total * ($item->tax_rate / 100);
        });
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->shipping_cost - $this->discount;
        $this->save();
    }
}

