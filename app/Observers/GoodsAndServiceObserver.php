<?php

namespace App\Observers;

use App\Models\GoodsAndService;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Log;

class GoodsAndServiceObserver
{
    public function created(GoodsAndService $item): void
    {
        $this->handleChange($item);
    }

    public function updated(GoodsAndService $item): void
    {
        $this->handleChange($item);
    }

    public function deleted(GoodsAndService $item): void
    {
        $this->handleChange($item);
    }

    protected function handleChange(GoodsAndService $item): void
    {
        AddyCacheManager::clearAgent('InventoryAgent', $item->organization_id);
        AddyCacheManager::clearOrganization($item->organization_id);
        
        // Regenerate insights with fresh data
        try {
            $organization = Organization::find($item->organization_id);
            if ($organization) {
                \App\Jobs\RegenerateAddyInsights::dispatch($organization)->delay(now()->addSeconds(5));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue insight regeneration', [
                'error' => $e->getMessage(),
                'organization_id' => $item->organization_id,
            ]);
        }
    }
}

