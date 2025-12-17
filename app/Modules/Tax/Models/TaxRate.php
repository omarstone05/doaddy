<?php

namespace App\Modules\Tax\Models;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'rate',
        'description',
        'is_default',
        'is_active',
        'tax_type',
        'metadata',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Ensure only one default tax rate per organization
        static::creating(function ($taxRate) {
            if ($taxRate->is_default) {
                static::where('organization_id', $taxRate->organization_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($taxRate) {
            if ($taxRate->is_default && $taxRate->getOriginal('is_default') !== true) {
                static::where('organization_id', $taxRate->organization_id)
                    ->where('id', '!=', $taxRate->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the formatted rate as a percentage string
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->rate, 2) . '%';
    }

    /**
     * Calculate tax amount from a base amount
     */
    public function calculateTax(float $amount): float
    {
        return round($amount * ($this->rate / 100), 2);
    }

    /**
     * Get the total amount including tax
     */
    public function calculateTotal(float $amount): float
    {
        return round($amount + $this->calculateTax($amount), 2);
    }

    /**
     * Scope to get active tax rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default tax rate
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}


