<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StockAdjustment extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'retail_stock_adjustments';

    protected $fillable = [
        'organization_id',
        'location_id',
        'adjustment_number',
        'adjustment_type',
        'status',
        'adjustment_date',
        'created_by',
        'approved_by',
        'reason',
        'notes',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($adjustment) {
            if (!$adjustment->adjustment_number) {
                $adjustment->adjustment_number = static::generateAdjustmentNumber($adjustment->organization_id);
            }
            if (!$adjustment->adjustment_date) {
                $adjustment->adjustment_date = now()->toDateString();
            }
        });
    }

    /**
     * Generate unique adjustment number
     */
    public static function generateAdjustmentNumber(string $organizationId): string
    {
        $prefix = 'ADJ-' . date('Y') . '-';
        $lastAdjustment = static::where('organization_id', $organizationId)
            ->where('adjustment_number', 'like', $prefix . '%')
            ->orderBy('adjustment_number', 'desc')
            ->first();

        if ($lastAdjustment) {
            $lastNumber = (int) str_replace($prefix, '', $lastAdjustment->adjustment_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id');
    }

    /**
     * Complete adjustment and update stock
     */
    public function complete(): void
    {
        if ($this->status === 'completed') {
            return;
        }

        foreach ($this->items as $item) {
            $product = $item->product;
            $difference = $item->difference;
            
            // Update stock
            $product->updateStock($difference, 'adjustment');
        }

        $this->status = 'completed';
        $this->save();
    }
}

