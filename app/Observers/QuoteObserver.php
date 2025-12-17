<?php

namespace App\Observers;

use App\Models\Quote;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Log;

class QuoteObserver
{
    public function created(Quote $quote): void
    {
        $this->handleChange($quote);
    }

    public function updated(Quote $quote): void
    {
        $this->handleChange($quote);
    }

    public function deleted(Quote $quote): void
    {
        $this->handleChange($quote);
    }

    protected function handleChange(Quote $quote): void
    {
        // Clear SalesAgent cache (quotes affect sales insights)
        AddyCacheManager::clearAgent('SalesAgent', $quote->organization_id);
        AddyCacheManager::clearOrganization($quote->organization_id);
        
        // Regenerate insights with fresh data
        try {
            $organization = Organization::find($quote->organization_id);
            if ($organization) {
                \App\Jobs\RegenerateAddyInsights::dispatch($organization)->delay(now()->addSeconds(5));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue insight regeneration', [
                'error' => $e->getMessage(),
                'organization_id' => $quote->organization_id,
            ]);
        }
    }
}

