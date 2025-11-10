<?php

namespace App\Observers;

use App\Models\GoodsAndService;
use App\Services\Addy\AddyCacheManager;

class GoodsAndServiceObserver
{
    public function created(GoodsAndService $item): void
    {
        $this->clearCache($item);
    }

    public function updated(GoodsAndService $item): void
    {
        $this->clearCache($item);
    }

    public function deleted(GoodsAndService $item): void
    {
        $this->clearCache($item);
    }

    protected function clearCache(GoodsAndService $item): void
    {
        AddyCacheManager::clearAgent('InventoryAgent', $item->organization_id);
        AddyCacheManager::clearOrganization($item->organization_id);
    }
}

