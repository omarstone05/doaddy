<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'print_job_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'paid_at',
        'status',
        'last_reminder_sent_at',
        'reminder_count',
        'last_reminder_channel',
        'last_reminder_notes',
        'notes',
        'terms',
        'payment_details',
        'quote_id',
        'is_recurring',
        'recurrence_frequency',
        'recurrence_day',
        'next_invoice_date',
        'recurrence_end_date',
        'parent_invoice_id',
        // DigiTax Smart Invoice fields
        'digitax_sale_id',
        'digitax_queue_status',
        'digitax_receipt_url',
        'digitax_receipt_number',
        'digitax_serial_number',
        'digitax_receipt_signature',
        'digitax_response',
        'digitax_error',
        'digitax_submitted_at',
        'digitax_completed_at',
        'digitax_retry_count',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'next_invoice_date' => 'date',
            'recurrence_end_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'last_reminder_sent_at' => 'datetime',
            'reminder_count' => 'integer',
            'paid_at' => 'datetime',
            'is_recurring' => 'boolean',
            'recurrence_day' => 'integer',
            'payment_details' => 'array',
            // DigiTax fields
            'digitax_response' => 'array',
            'digitax_error' => 'array',
            'digitax_submitted_at' => 'datetime',
            'digitax_completed_at' => 'datetime',
            'digitax_retry_count' => 'integer',
        ];
    }

    /**
     * Check if this invoice has been submitted to DigiTax
     */
    public function isDigitaxSubmitted(): bool
    {
        return !empty($this->digitax_sale_id);
    }

    /**
     * Check if DigiTax processing is complete and receipt is available
     */
    public function hasDigitaxReceipt(): bool
    {
        return $this->digitax_queue_status === 'complete' && !empty($this->digitax_receipt_url);
    }

    /**
     * Check if DigiTax submission failed
     */
    public function isDigitaxFailed(): bool
    {
        return $this->digitax_queue_status === 'failed';
    }

    /**
     * Check if DigiTax is still processing
     */
    public function isDigitaxProcessing(): bool
    {
        return in_array($this->digitax_queue_status, ['queued', 'processing']);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'parent_invoice_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Link to CRM Quote (if CRM module is enabled and quote was converted from CRM)
     */
    public function crmQuote(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        if (class_exists(\App\Modules\CRM\Models\Quote::class)) {
            return $this->hasOne(\App\Modules\CRM\Models\Quote::class, 'invoice_id');
        }
        throw new \Exception('CRM module not available');
    }

    /**
     * Get the print job this invoice is for (if PrintShop module is enabled)
     * Returns null if module is disabled or print_job_id is null
     */
    public function printJob(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Print\PrintJob::class, 'print_job_id');
    }

    public function getBalanceAttribute(): float
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getAmountDueAttribute(): float
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && 
               $this->status !== 'paid' && 
               $this->status !== 'cancelled' &&
               now()->isAfter($this->due_date);
    }

    protected static function booted(): void
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->organization_id);
            }
        });

        static::created(function ($invoice) {
            if ($invoice->is_overdue) {
                $invoice->update(['status' => 'overdue']);
            }
        });
    }

    public static function generateInvoiceNumber($organizationId): string
    {
        $date = now()->format('Ymd');
        $prefix = "INV-{$date}-";
        
        $lastInvoice = static::where('organization_id', $organizationId)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) str_replace($prefix, '', $lastInvoice->invoice_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
