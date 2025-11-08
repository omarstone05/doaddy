<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function booted(): void
    {
        static::created(function ($allocation) {
            // Update invoice paid amount
            $invoice = $allocation->invoice;
            $invoice->increment('paid_amount', $allocation->amount);

            // Update invoice status
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->update(['status' => 'paid']);
            } elseif ($invoice->paid_amount > 0) {
                $invoice->update(['status' => 'sent']);
            }
        });

        static::deleted(function ($allocation) {
            // Update invoice paid amount
            $invoice = $allocation->invoice;
            $invoice->decrement('paid_amount', $allocation->amount);

            // Update invoice status
            if ($invoice->paid_amount == 0) {
                $invoice->update(['status' => 'sent']);
            }
        });
    }
}
