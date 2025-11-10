<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\Addy\AddyCacheManager;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    public function updated(Invoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    public function deleted(Invoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    protected function clearCache(Invoice $invoice): void
    {
        // Clear SalesAgent cache
        AddyCacheManager::clearAgent('SalesAgent', $invoice->organization_id);
        
        // Clear organization cache (affects cross-section insights)
        AddyCacheManager::clearOrganization($invoice->organization_id);
    }
}

