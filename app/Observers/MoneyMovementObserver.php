<?php

namespace App\Observers;

use App\Models\MoneyMovement;
use App\Services\Addy\AddyCacheManager;

class MoneyMovementObserver
{
    public function created(MoneyMovement $movement): void
    {
        $this->clearCache($movement);
    }

    public function updated(MoneyMovement $movement): void
    {
        $this->clearCache($movement);
    }

    public function deleted(MoneyMovement $movement): void
    {
        $this->clearCache($movement);
    }

    protected function clearCache(MoneyMovement $movement): void
    {
        // Clear MoneyAgent cache for this organization
        AddyCacheManager::clearAgent('MoneyAgent', $movement->organization_id);
        
        // Also clear decision loop cache (cross-section insights might change)
        AddyCacheManager::clearOrganization($movement->organization_id);
    }
}

