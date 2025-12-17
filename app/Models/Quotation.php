<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'prospect_id',
        'customer_id',
        'print_job_id',
        'created_by',
        'quotation_number',
        'title',
        'description',
        'status',
        'issue_date',
        'valid_until',
        'sent_at',
        'viewed_at',
        'responded_at',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'tax_percentage',
        'total',
        'currency',
        'terms_and_conditions',
        'payment_terms',
        'delivery_terms',
        'validity_days',
        'converted_to_invoice_id',
        'converted_at',
        'conversion_probability',
        'rejection_reason',
        'follow_up_date',
        'follow_up_completed',
        'internal_notes',
        'tags',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'date',
        'viewed_at' => 'date',
        'responded_at' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'total' => 'decimal:2',
        'converted_at' => 'datetime',
        'conversion_probability' => 'decimal:2',
        'follow_up_date' => 'datetime',
        'follow_up_completed' => 'boolean',
        'tags' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quotation) {
            if (empty($quotation->quotation_number)) {
                $quotation->quotation_number = self::generateQuotationNumber();
            }
            if (empty($quotation->valid_until)) {
                $quotation->valid_until = now()->addDays($quotation->validity_days ?? 30);
            }
        });
    }

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_to_invoice_id');
    }

    /**
     * Get the print job this quotation is for (if PrintShop module is enabled)
     * Returns null if module is disabled or print_job_id is null
     */
    public function printJob(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Print\PrintJob::class, 'print_job_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'viewed']);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', '!=', 'expired')
            ->where('valid_until', '<', now());
    }

    public function scopeAwaitingResponse($query)
    {
        return $query->whereIn('status', ['sent', 'viewed'])
            ->where('valid_until', '>=', now());
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until < now() && !in_array($this->status, ['accepted', 'rejected', 'expired']);
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return max(0, now()->diffInDays($this->valid_until, false));
    }

    public function getClientAttribute()
    {
        return $this->prospect ?? $this->customer;
    }

    public function getClientNameAttribute(): string
    {
        if ($this->prospect) {
            return $this->prospect->company_name ?: $this->prospect->name;
        }
        return $this->customer->name ?? 'Unknown';
    }

    // Methods
    public static function generateQuotationNumber(): string
    {
        $prefix = 'QUO';
        $year = date('Y');
        $lastQuotation = self::whereYear('created_at', $year)->latest('id')->first();
        $number = $lastQuotation ? ((int) substr($lastQuotation->quotation_number, -4)) + 1 : 1;
        
        return $prefix . '-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(): void
    {
        // Ensure items are loaded
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        
        $this->subtotal = $this->items->sum('total');
        
        if ($this->discount_percentage > 0) {
            $this->discount_amount = ($this->subtotal * $this->discount_percentage) / 100;
        }
        
        $amountAfterDiscount = $this->subtotal - $this->discount_amount;
        
        if ($this->tax_percentage > 0) {
            $this->tax_amount = ($amountAfterDiscount * $this->tax_percentage) / 100;
        }
        
        $this->total = $amountAfterDiscount + $this->tax_amount;
        $this->save();
    }

    public function markAsSent(): void
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
    }

    public function markAsViewed(): void
    {
        if ($this->status === 'sent') {
            $this->status = 'viewed';
            $this->viewed_at = now();
            $this->save();
        }
    }

    public function accept(): void
    {
        $this->status = 'accepted';
        $this->responded_at = now();
        $this->save();
    }

    public function reject(string $reason = null): void
    {
        $this->status = 'rejected';
        $this->responded_at = now();
        if ($reason) {
            $this->rejection_reason = $reason;
        }
        $this->save();
    }

    public function checkAndMarkExpired(): void
    {
        if ($this->is_expired) {
            $this->status = 'expired';
            $this->save();
        }
    }

    public function convertToInvoice(): Invoice
    {
        \DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'organization_id' => $this->organization_id,
                'customer_id' => $this->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $this->subtotal,
                'discount_amount' => $this->discount_amount,
                'tax_amount' => $this->tax_amount,
                'total_amount' => $this->total,
                'status' => 'sent',
                'notes' => $this->internal_notes,
                'terms' => $this->terms_and_conditions,
            ]);

            // Copy quotation items to invoice items
            foreach ($this->items as $index => $item) {
                \App\Models\InvoiceItem::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'goods_service_id' => $item->product_id, // Map product_id to goods_service_id
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                    'display_order' => $index,
                ]);
            }

            $this->converted_to_invoice_id = $invoice->id;
            $this->converted_at = now();
            $this->save();

            \DB::commit();

            return $invoice;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
