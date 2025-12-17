<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        $this->handleChange($invoice);
    }

    public function updated(Invoice $invoice): void
    {
        $this->handleChange($invoice);
    }

    public function deleted(Invoice $invoice): void
    {
        $this->handleChange($invoice);
    }

    protected function handleChange(Invoice $invoice): void
    {
        // Clear SalesAgent cache
        AddyCacheManager::clearAgent('SalesAgent', $invoice->organization_id);
        
        // Clear organization cache (affects cross-section insights)
        AddyCacheManager::clearOrganization($invoice->organization_id);
        
        // Regenerate insights with fresh data (queue it to avoid blocking)
        try {
            $organization = Organization::find($invoice->organization_id);
            if ($organization) {
                // Use queue to avoid blocking the request
                \App\Jobs\RegenerateAddyInsights::dispatch($organization)->delay(now()->addSeconds(5));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue insight regeneration', [
                'error' => $e->getMessage(),
                'organization_id' => $invoice->organization_id,
            ]);
        }
    }
}

