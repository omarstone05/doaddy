<?php

namespace Addy\Modules\SmartInvoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitaxCredential extends Model
{
    protected $table = 'digitax_credentials';

    protected $fillable = [
        'organization_id',
        'api_key',
        'api_secret',
        'environment',
        'is_active',
        'last_tested_at',
        'test_result',
        'test_error',
    ];

    protected $hidden = [
        'api_secret', // Never expose secret in API responses
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
     */
    public function getApiUrl(): string
    {
        $baseUrl = match($this->environment) {
            'production' => 'https://api.digitax.io',
            default => 'https://sandbox-api.digitax.io',
        };

        return $baseUrl;
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
