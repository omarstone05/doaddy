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
use Illuminate\Support\Facades\Log;

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

        // Use 'creating' event with higher priority to ensure organization_id is set first
        // The BelongsToOrganization trait also uses 'creating', so we need to run after it
        static::creating(function ($customer) {
            // Ensure organization_id is set (BelongsToOrganization trait should set it, but ensure it)
            if (empty($customer->organization_id) && auth()->check() && auth()->user()->organization_id) {
                $customer->organization_id = auth()->user()->organization_id;
            }
            
            // Generate customer_code if not set
            // customer_code must be globally unique (across all organizations)
            if (empty($customer->customer_code)) {
                try {
                    $customer->customer_code = self::generateCustomerCode();
                } catch (\Exception $e) {
                    Log::error('Failed to generate customer code: ' . $e->getMessage(), [
                        'exception' => $e,
                        'organization_id' => $customer->organization_id ?? null,
                    ]);
                    // Fallback: use UUID-based code
                    $customer->customer_code = 'CUS' . strtoupper(substr(str_replace('-', '', (string) \Illuminate\Support\Str::uuid()), 0, 9));
                }
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
    public static function generateCustomerCode(?string $organizationId = null): string
    {
        $prefix = 'CUS';
        $number = 1;
        
        // Use created_at for ordering since we're using UUIDs
        // NOTE: customer_code is UNIQUE globally (across all organizations), not per-organization
        try {
            // Use withoutGlobalScopes to avoid the BelongsToOrganization scope interfering
            // We need to check globally, not per-organization
            $lastCustomer = self::withoutGlobalScopes()
                ->whereNotNull('customer_code')
                ->where('customer_code', 'like', $prefix . '%')
                ->latest('created_at')
                ->first();
            
            if ($lastCustomer && !empty($lastCustomer->customer_code)) {
                // Extract number from customer_code (format: CUS000001)
                $code = $lastCustomer->customer_code;
                if (strlen($code) > 3 && is_numeric(substr($code, 3))) {
                    $number = (int) substr($code, 3) + 1;
                }
            }
        } catch (\Exception $e) {
            // If query fails, start from 1
            Log::warning('Failed to generate customer code from existing customers: ' . $e->getMessage());
            $number = 1;
        }
        
        // Ensure GLOBAL uniqueness (customer_code is unique across all organizations)
        $maxAttempts = 100;
        $attempts = 0;
        $customerCode = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
        
        // Check GLOBAL uniqueness without global scopes
        while (self::withoutGlobalScopes()->where('customer_code', $customerCode)->exists() && $attempts < $maxAttempts) {
            $number++;
            $customerCode = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
            $attempts++;
        }
        
        if ($attempts >= $maxAttempts) {
            // Fallback: use timestamp-based code if we can't find a unique sequential one
            $timestamp = date('YmdHis');
            $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $customerCode = $prefix . $timestamp . $random;
            
            // Double-check this is unique
            $attempts = 0;
            while (self::withoutGlobalScopes()->where('customer_code', $customerCode)->exists() && $attempts < 10) {
                $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $customerCode = $prefix . $timestamp . $random;
                $attempts++;
            }
        }
        
        return $customerCode;
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
