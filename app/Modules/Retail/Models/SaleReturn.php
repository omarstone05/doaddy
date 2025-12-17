<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use App\Models\MoneyMovement;
use App\Models\StockMovement;
use App\Models\GoodsAndService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SaleReturn extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $table = 'sale_returns';

    protected $fillable = [
        'organization_id',
        'sale_id',
        'return_number',
        'return_amount',
        'return_reason',
        'refund_method',
        'refund_reference',
        'processed_by_id',
        'status',
        'return_date',
    ];

    protected function casts(): array
    {
        return [
            'return_amount' => 'decimal:2',
            'return_date' => 'date',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TeamMember::class, 'processed_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    protected static function booted(): void
    {
        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $return->return_number = static::generateReturnNumber($return->organization_id);
            }
        });

        static::created(function ($return) {
            // Reload return with items before processing
            $return->refresh();
            $return->load('items');

            // Create money movement for refund
            if ($return->refund_method !== 'credit_note') {
                MoneyMovement::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $return->organization_id,
                    'flow_type' => 'expense',
                    'amount' => $return->return_amount,
                    'currency' => 'ZMW',
                    'transaction_date' => $return->return_date,
                    'from_account_id' => $return->sale->money_account_id,
                    'description' => "Refund for Return {$return->return_number}",
                    'related_type' => 'SaleReturn',
                    'related_id' => $return->id,
                    'status' => 'approved',
                    'created_by_id' => $return->processed_by_id,
                ]);
            }

            // Create stock movements to add back inventory
            foreach ($return->items as $item) {
                StockMovement::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $return->organization_id,
                    'goods_service_id' => $item->saleItem->goods_service_id,
                    'movement_type' => 'in',
                    'quantity' => $item->quantity_returned,
                    'reference_number' => $return->return_number,
                    'notes' => "Return from sale",
                    'created_by_id' => $return->processed_by_id,
                ]);

                // Update current stock
                $product = GoodsAndService::find($item->saleItem->goods_service_id);
                if ($product && $product->track_stock) {
                    $product->increment('current_stock', $item->quantity_returned);
                }
            }

            // Update sale status
            $return->sale->update(['status' => 'returned']);
        });
    }

    protected static function generateReturnNumber($organizationId): string
    {
        $date = now()->format('Ymd');
        $prefix = "RET-{$date}-";
        
        $lastReturn = static::where('organization_id', $organizationId)
            ->where('return_number', 'like', $prefix . '%')
            ->orderBy('return_number', 'desc')
            ->first();

        if ($lastReturn) {
            $lastNumber = (int) str_replace($prefix, '', $lastReturn->return_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}

