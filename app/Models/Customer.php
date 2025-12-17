<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes, HasFactory;

    protected $fillable = [
        'organization_id',
        'customer_persona_id',
        'customer_code',
        'type',
        'name',
        'email',
        'phone',
        'website',
        'tax_id',
        'billing_address',
        'shipping_address',
        'city',
        'state',
        'country',
        'postal_code',
        'credit_limit',
        'payment_terms',
        'custom_payment_days',
        'currency',
        'lifetime_value',
        'outstanding_balance',
        'status',
        'first_purchase_date',
        'last_purchase_date',
        'total_orders',
        'average_order_value',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'notes',
        'tags',
        'custom_fields',
    ];

    protected $casts = [
        'first_purchase_date' => 'date',
        'last_purchase_date' => 'date',
        'credit_limit' => 'decimal:2',
        'lifetime_value' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'tags' => 'array',
        'custom_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = self::generateCustomerCode();
            }
        });
    }

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(CustomerPersona::class, 'customer_persona_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function crmContact(): HasOne
    {
        return $this->hasOne(\App\Modules\CRM\Models\Contact::class, 'customer_id');
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

    public function getPendingInvoicesAttribute()
    {
        return $this->invoices()
            ->whereIn('status', ['sent', 'overdue'])
            ->get();
    }

    public function getProjectedIncomeAttribute(): float
    {
        return $this->invoices()
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('amount_due');
    }

    // Methods
    public static function generateCustomerCode(): string
    {
        $prefix = 'CUS';
        $lastCustomer = self::latest('id')->first();
        $number = $lastCustomer ? ((int) substr($lastCustomer->customer_code, 3)) + 1 : 1;
        
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function updateFinancialMetrics(): void
    {
        $this->lifetime_value = $this->invoices()
            ->where('status', 'paid')
            ->sum('total');

        $this->outstanding_balance = $this->invoices()
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->sum('amount_due');

        $this->total_orders = $this->invoices()->count();

        if ($this->total_orders > 0) {
            $this->average_order_value = $this->lifetime_value / $this->total_orders;
        }

        $this->save();
    }
}
