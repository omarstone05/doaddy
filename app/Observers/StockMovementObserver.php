<?php

namespace App\Observers;

use App\Models\StockMovement;
use App\Services\Addy\AddyCacheManager;

class StockMovementObserver
{
    public function created(StockMovement $movement): void
    {
        $this->clearCache($movement);
    }

    public function updated(StockMovement $movement): void
    {
        $this->clearCache($movement);
    }

    public function deleted(StockMovement $movement): void
    {
        $this->clearCache($movement);
    }

    protected function clearCache(StockMovement $movement): void
    {
        AddyCacheManager::clearAgent('InventoryAgent', $movement->organization_id);
        AddyCacheManager::clearOrganization($movement->organization_id);
    }
}

