<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sale extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'retail_sales';

    protected $fillable = [
        'organization_id',
        'location_id',
        'sale_number',
        'transaction_type',
        'status',
        'sale_date',
        'sale_time',
        'customer_id',
        'customer_name',
        'customer_phone',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'change_given',
        'currency',
        'payment_method',
        'mobile_money_provider',
        'mobile_money_number',
        'card_last_four',
        'cashier_id',
        'shift_id',
        'total_cost',
        'total_profit',
        'profit_margin',
        'notes',
        'receipt_printed',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_given' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'receipt_printed' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (!$sale->sale_number) {
                $sale->sale_number = static::generateSaleNumber($sale->organization_id);
            }
            if (!$sale->sale_date) {
                $sale->sale_date = now()->toDateString();
            }
            if (!$sale->sale_time) {
                $sale->sale_time = now()->toTimeString();
            }
        });
    }

    /**
     * Generate unique sale number
     */
    public static function generateSaleNumber(string $organizationId): string
    {
        $prefix = 'SALE-' . date('Y') . '-';
        $lastSale = static::where('organization_id', $organizationId)
            ->where('sale_number', 'like', $prefix . '%')
            ->orderBy('sale_number', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) str_replace($prefix, '', $lastSale->sale_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cashier_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('line_total');
        $this->tax_amount = $this->items->sum(function ($item) {
            return ($item->line_total - $item->discount_per_item) * ($item->tax_rate / 100);
        });
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        
        // Calculate profit
        $this->total_cost = $this->items->sum('line_cost');
        $this->total_profit = $this->total_amount - $this->total_cost;
        $this->profit_margin = $this->total_amount > 0 
            ? ($this->total_profit / $this->total_amount) * 100 
            : 0;
        
        $this->save();
    }
}
