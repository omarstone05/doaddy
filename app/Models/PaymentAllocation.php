<?php

namespace App\Models;

use App\Services\GamificationPublisher;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

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
            $wasPaid = $invoice->status === 'paid';
            $invoice->increment('paid_amount', $allocation->amount);

            // Update invoice status
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->update(['status' => 'paid']);
            } elseif ($invoice->paid_amount > 0) {
                $invoice->update(['status' => 'sent']);
            }

            if (!$wasPaid && $invoice->paid_amount >= $invoice->total_amount) {
                try {
                    $payment = $allocation->payment;
                    $paymentDate = $payment?->payment_date ?? now();
                    $invoiceDate = $invoice->invoice_date ?? $invoice->created_at;
                    $daysToPay = $invoiceDate ? $paymentDate->diffInDays($invoiceDate) : null;

                    app(GamificationPublisher::class)->publish('invoice_paid', [
                        'invoice_number' => $invoice->invoice_number,
                        'payment_amount' => $allocation->amount,
                        'payment_method' => $payment?->payment_method,
                        'days_to_pay' => $daysToPay,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Gamification invoice_paid event failed', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);
                }
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
