<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use App\Models\MoneyMovement;
use App\Models\StockMovement;
use App\Models\GoodsAndService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\CommissionRule;
use App\Models\CommissionEarning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sale extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'sale_number',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'payment_method',
        'payment_reference',
        'customer_id',
        'customer_name',
        'money_account_id',
        'department_id',
        'cashier_id',
        'register_session_id',
        'status',
        'sale_date',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'sale_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function moneyAccount(): BelongsTo
    {
        return $this->belongsTo(MoneyAccount::class);
    }

    public function registerSession(): BelongsTo
    {
        return $this->belongsTo(RegisterSession::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function commissionEarnings(): HasMany
    {
        return $this->hasMany(CommissionEarning::class);
    }

    public function getNetAmountAttribute(): float
    {
        return $this->total_amount - $this->tax_amount - $this->discount_amount;
    }

    protected static function booted(): void
    {
        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = static::generateSaleNumber($sale->organization_id);
            }
        });

        static::created(function ($sale) {
            // Reload sale with items before processing
            $sale->refresh();
            $sale->load('items');
            
            // Auto-create money movement
            if ($sale->payment_method !== 'credit') {
                MoneyMovement::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $sale->organization_id,
                    'flow_type' => 'income',
                    'amount' => $sale->total_amount,
                    'currency' => 'ZMW',
                    'transaction_date' => $sale->sale_date,
                    'to_account_id' => $sale->money_account_id,
                    'description' => "Sale {$sale->sale_number}",
                    'related_type' => 'Sale',
                    'related_id' => $sale->id,
                    'status' => 'approved',
                    'created_by_id' => auth()->id(),
                ]);
            }

            // Auto-create stock movements
            foreach ($sale->items as $item) {
                StockMovement::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $sale->organization_id,
                    'goods_service_id' => $item->goods_service_id,
                    'movement_type' => 'out',
                    'quantity' => $item->quantity,
                    'reference_number' => $sale->sale_number,
                    'notes' => "Sale to customer",
                    'created_by_id' => $sale->cashier_id,
                ]);

                // Update current stock
                $product = GoodsAndService::find($item->goods_service_id);
                if ($product && $product->track_stock) {
                    $product->decrement('current_stock', $item->quantity);
                }
            }

            // Auto-create commission earnings if cashier exists
            if ($sale->cashier_id) {
                $commissionRules = CommissionRule::where('organization_id', $sale->organization_id)
                    ->where('is_active', true)
                    ->where(function ($q) use ($sale) {
                        $q->where('applicable_to', 'all')
                          ->orWhere(function ($q2) use ($sale) {
                              $q2->where('applicable_to', 'team_member')
                                 ->where('team_member_id', $sale->cashier_id);
                          })
                          ->orWhere(function ($q3) use ($sale) {
                              $q3->where('applicable_to', 'department')
                                 ->where('department_id', $sale->cashier->department_id);
                          });
                    })
                    ->get();

                foreach ($commissionRules as $rule) {
                    $commissionAmount = $rule->calculateCommission($sale->total_amount);
                    
                    if ($commissionAmount > 0) {
                        CommissionEarning::create([
                            'id' => (string) Str::uuid(),
                            'organization_id' => $sale->organization_id,
                            'team_member_id' => $sale->cashier_id,
                            'sale_id' => $sale->id,
                            'commission_rule_id' => $rule->id,
                            'amount' => $commissionAmount,
                            'sale_amount' => $sale->total_amount,
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        });
    }

    protected static function generateSaleNumber($organizationId): string
    {
        $date = now()->format('Ymd');
        $prefix = "SALE-{$date}-";
        
        $lastSale = static::where('organization_id', $organizationId)
            ->where('sale_number', 'like', $prefix . '%')
            ->orderBy('sale_number', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) str_replace($prefix, '', $lastSale->sale_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
