<?php

namespace App\Observers;

use App\Models\StockMovement;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Log;

class StockMovementObserver
{
    public function created(StockMovement $movement): void
    {
        $this->handleChange($movement);
    }

    public function updated(StockMovement $movement): void
    {
        $this->handleChange($movement);
    }

    public function deleted(StockMovement $movement): void
    {
        $this->handleChange($movement);
    }

    protected function handleChange(StockMovement $movement): void
    {
        AddyCacheManager::clearAgent('InventoryAgent', $movement->organization_id);
        AddyCacheManager::clearOrganization($movement->organization_id);
        
        // Regenerate insights with fresh data
        try {
            $organization = Organization::find($movement->organization_id);
            if ($organization) {
                \App\Jobs\RegenerateAddyInsights::dispatch($organization)->delay(now()->addSeconds(5));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue insight regeneration', [
                'error' => $e->getMessage(),
                'organization_id' => $movement->organization_id,
            ]);
        }
    }
}

