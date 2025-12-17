<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'crm_quotes';

    protected $fillable = [
        'organization_id',
        'quote_number',
        'quote_name',
        'version',
        'parent_quote_id',
        'contact_id',
        'opportunity_id',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'total_amount',
        'currency',
        'quote_date',
        'expiry_date',
        'sent_date',
        'accepted_date',
        'declined_date',
        'status',
        'is_primary',
        'payment_terms',
        'delivery_terms',
        'validity_period_days',
        'terms_and_conditions',
        'notes',
        'owner_id',
        'prepared_by',
        'approved_by',
        'converted_to_invoice',
        'invoice_id',
        'existing_quote_id',
        'converted_at',
        'template_id',
        'requires_signature',
        'signed',
        'signature_data',
        'signed_by_name',
        'signed_at',
        'signature_ip',
        'view_count',
        'last_viewed_at',
        'custom_fields',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'expiry_date' => 'date',
        'sent_date' => 'date',
        'accepted_date' => 'date',
        'declined_date' => 'date',
        'signed_at' => 'datetime',
        'converted_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_primary' => 'boolean',
        'converted_to_invoice' => 'boolean',
        'requires_signature' => 'boolean',
        'signed' => 'boolean',
        'custom_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            if (!$quote->quote_number) {
                $quote->quote_number = static::generateQuoteNumber($quote->organization_id);
            }
            if (!$quote->quote_date) {
                $quote->quote_date = now()->toDateString();
            }
            if (!$quote->expiry_date && $quote->validity_period_days) {
                $quote->expiry_date = now()->addDays($quote->validity_period_days)->toDateString();
            }
        });
    }

    public static function generateQuoteNumber(string $organizationId): string
    {
        $prefix = 'QUO-' . date('Y') . '-';
        $lastQuote = static::where('organization_id', $organizationId)
            ->where('quote_number', 'like', $prefix . '%')
            ->orderBy('quote_number', 'desc')
            ->first();

        if ($lastQuote) {
            $lastNumber = (int) str_replace($prefix, '', $lastQuote->quote_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'quote_id');
    }

    public function parentQuote(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_quote_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_quote_id');
    }

    /**
     * Link to existing Invoice model (when CRM quote is converted to invoice)
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'invoice_id');
    }

    /**
     * Link to existing Quote model (for syncing between CRM and core quotes)
     */
    public function existingQuote(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Quote::class, 'existing_quote_id');
    }
}


