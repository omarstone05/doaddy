<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization, SoftDeletes;

    protected static function newFactory()
    {
        return VendorFactory::new();
    }

    protected $fillable = [
        'organization_id',
        'vendor_code',
        'type',
        'name',
        'email',
        'phone',
        'website',
        'tax_id',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'payment_terms',
        'custom_payment_days',
        'currency',
        'total_spent',
        'outstanding_balance',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'payment_method',
        'status',
        'rating',
        'first_transaction_date',
        'last_transaction_date',
        'total_transactions',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'notes',
        'tags',
        'custom_fields',
    ];

    protected $casts = [
        'first_transaction_date' => 'date',
        'last_transaction_date' => 'date',
        'total_spent' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'tags' => 'array',
        'custom_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->vendor_code)) {
                $vendor->vendor_code = self::generateVendorCode();
            }
        });
    }

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithOutstandingBalance($query)
    {
        return $query->where('outstanding_balance', '>', 0);
    }

    // Accessors
    public function getPaymentTermsDaysAttribute(): int
    {
        return match($this->payment_terms) {
            'immediate' => 0,
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            'net_90' => 90,
            'custom' => $this->custom_payment_days ?? 30,
            default => 30,
        };
    }

    public function getUpcomingLiabilitiesAttribute()
    {
        return $this->bills()
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getTotalLiabilitiesAttribute(): float
    {
        return $this->bills()
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->sum('amount_due');
    }

    // Methods
    public static function generateVendorCode(): string
    {
        $prefix = 'VEN';
        $lastVendor = self::latest('id')->first();
        $number = $lastVendor ? ((int) substr($lastVendor->vendor_code, 3)) + 1 : 1;
        
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function updateFinancialMetrics(): void
    {
        $this->total_spent = $this->bills()
            ->where('payment_status', 'paid')
            ->sum('total');

        $this->outstanding_balance = $this->bills()
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->sum('amount_due');

        $this->total_transactions = $this->bills()->count();

        $this->save();
    }
}
