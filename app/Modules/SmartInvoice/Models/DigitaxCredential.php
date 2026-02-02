<?php

namespace App\Modules\SmartInvoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitaxCredential extends Model
{
    protected $table = 'digitax_credentials';

    protected $fillable = [
        'organization_id',
        'api_key',                  // Serial Number (e.g., NAMI26012180421379KB7DAE)
        'api_secret',               // TPIN
        'digitax_api_key',          // Digitax API Key (e.g., api_key_KC4gxhqWqcYlgdpnJdVBoyE34fjAChOn)
        'environment',
        'is_active',
        'last_tested_at',
        'test_result',
        'test_error',
    ];

    protected $hidden = [
        'api_secret',               // Never expose secret in API responses
        'digitax_api_key',          // Never expose Digitax API key in API responses
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
        'test_result' => 'array',
    ];

    /**
     * Get the organization that owns this credential
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo('Modules\Organization\Models\Organization');
    }

    /**
     * Scope to get active credentials only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by environment
     */
    public function scopeEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Get the full API URL
     * 
     * DigiTax uses a single API URL for both sandbox and production.
     * The distinction is made by whether the business is marked as TEST or LIVE
     * in the DigiTax dashboard, not by different URLs.
     * 
     * @see https://zm.docs.digitax.tech/docs/getting-started
     */
    public function getApiUrl(): string
    {
        // DigiTax Zambia uses a single API endpoint
        // Sandbox/Production is determined by business type (TEST/LIVE) in DigiTax dashboard
        return 'https://api.digitax.tech/zm/v1';
    }

    /**
     * Check if credentials are valid (based on test result)
     */
    public function isValid(): bool
    {
        return $this->test_result && 
               isset($this->test_result['success']) && 
               $this->test_result['success'] === true;
    }

    /**
     * Get days since last test
     */
    public function daysSinceLastTest(): ?int
    {
        if (!$this->last_tested_at) {
            return null;
        }

        return now()->diffInDays($this->last_tested_at);
    }
}
