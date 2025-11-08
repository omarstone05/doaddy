<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use App\Models\MoneyMovement;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'payment_number',
        'amount',
        'currency',
        'payment_date',
        'payment_method',
        'payment_reference',
        'money_account_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function moneyAccount(): BelongsTo
    {
        return $this->belongsTo(MoneyAccount::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function getAllocatedAmountAttribute(): float
    {
        return $this->allocations->sum('amount');
    }

    public function getUnallocatedAmountAttribute(): float
    {
        return $this->amount - $this->allocated_amount;
    }

    protected static function booted(): void
    {
        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber($payment->organization_id);
            }
        });

        static::created(function ($payment) {
            // Auto-create money movement
            MoneyMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $payment->organization_id,
                'flow_type' => 'income',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'transaction_date' => $payment->payment_date,
                'to_account_id' => $payment->money_account_id,
                'description' => "Payment {$payment->payment_number}",
                'related_type' => 'Payment',
                'related_id' => $payment->id,
                'status' => 'approved',
                'created_by_id' => auth()->id(),
            ]);

            // Auto-generate receipt
            Receipt::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $payment->organization_id,
                'payment_id' => $payment->id,
                'receipt_number' => static::generateReceiptNumber($payment->organization_id),
                'receipt_date' => $payment->payment_date,
            ]);
        });
    }

    protected static function generatePaymentNumber($organizationId): string
    {
        $date = now()->format('Ymd');
        $prefix = "PAY-{$date}-";
        
        $lastPayment = static::where('organization_id', $organizationId)
            ->where('payment_number', 'like', $prefix . '%')
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) str_replace($prefix, '', $lastPayment->payment_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    protected static function generateReceiptNumber($organizationId): string
    {
        $date = now()->format('Ymd');
        $prefix = "RCP-{$date}-";
        
        $lastReceipt = Receipt::where('organization_id', $organizationId)
            ->where('receipt_number', 'like', $prefix . '%')
            ->orderBy('receipt_number', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) str_replace($prefix, '', $lastReceipt->receipt_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
