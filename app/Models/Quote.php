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

class Quote extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'quote_number',
        'quote_date',
        'expiry_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'follow_up_count',
        'last_follow_up_at',
        'last_follow_up_method',
        'last_follow_up_notes',
        'notes',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'expiry_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'follow_up_count' => 'integer',
            'last_follow_up_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(\App\Models\Attachment::class, 'attachable');
    }

    public function getNetAmountAttribute(): float
    {
        return $this->total_amount - $this->tax_amount - $this->discount_amount;
    }

    protected static function booted(): void
    {
        static::creating(function ($quote) {
            if (empty($quote->quote_number)) {
                $quote->quote_number = static::generateQuoteNumber($quote->organization_id);
            }
        });
    }

    protected static function generateQuoteNumber($organizationId): string
    {
        $date = now()->format('Ymd');
        $prefix = "QUOTE-{$date}-";
        
        $lastQuote = static::where('organization_id', $organizationId)
            ->where('quote_number', 'like', $prefix . '%')
            ->orderBy('quote_number', 'desc')
            ->first();

        if ($lastQuote) {
            $lastNumber = (int) str_replace($prefix, '', $lastQuote->quote_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
