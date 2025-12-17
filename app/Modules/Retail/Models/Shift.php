<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Shift extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'retail_shifts';

    protected $fillable = [
        'organization_id',
        'location_id',
        'shift_number',
        'cashier_id',
        'status',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'opened_at',
        'closed_at',
        'total_sales',
        'total_cash_sales',
        'total_mobile_money_sales',
        'total_card_sales',
        'total_refunds',
        'number_of_sales',
        'number_of_refunds',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_mobile_money_sales' => 'decimal:2',
        'total_card_sales' => 'decimal:2',
        'total_refunds' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shift) {
            if (!$shift->shift_number) {
                $shift->shift_number = static::generateShiftNumber($shift->organization_id);
            }
            if (!$shift->opened_at) {
                $shift->opened_at = now();
            }
        });
    }

    /**
     * Generate unique shift number
     */
    public static function generateShiftNumber(string $organizationId): string
    {
        $prefix = 'SHIFT-' . date('Ymd') . '-';
        $lastShift = static::where('organization_id', $organizationId)
            ->where('shift_number', 'like', $prefix . '%')
            ->orderBy('shift_number', 'desc')
            ->first();

        if ($lastShift) {
            $lastNumber = (int) str_replace($prefix, '', $lastShift->shift_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cashier_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'shift_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'shift_id');
    }

    /**
     * Calculate totals from sales
     */
    public function calculateTotals(): void
    {
        $sales = $this->sales()->where('status', 'completed')->get();
        
        $this->total_sales = $sales->sum('total_amount');
        $this->total_cash_sales = $sales->where('payment_method', 'cash')->sum('total_amount');
        $this->total_mobile_money_sales = $sales->where('payment_method', 'mobile_money')->sum('total_amount');
        $this->total_card_sales = $sales->where('payment_method', 'card')->sum('total_amount');
        $this->total_refunds = $sales->where('transaction_type', 'return')->sum('total_amount');
        $this->number_of_sales = $sales->where('transaction_type', 'sale')->count();
        $this->number_of_refunds = $sales->where('transaction_type', 'return')->count();
        
        // Calculate expected cash
        $cashIn = $this->cashMovements()->where('movement_type', 'cash_in')->sum('amount');
        $cashOut = $this->cashMovements()->where('movement_type', 'cash_out')->sum('amount');
        $this->expected_cash = $this->opening_cash + $this->total_cash_sales + $cashIn - $cashOut;
        
        if ($this->closing_cash) {
            $this->cash_difference = $this->closing_cash - $this->expected_cash;
        }
        
        $this->save();
    }

    /**
     * Close shift
     */
    public function close(float $closingCash, ?string $notes = null): void
    {
        $this->closing_cash = $closingCash;
        $this->status = 'closed';
        $this->closed_at = now();
        $this->notes = $notes;
        $this->calculateTotals();
    }
}

