<?php

namespace App\Observers;

use App\Models\MoneyMovement;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Log;

class MoneyMovementObserver
{
    public function created(MoneyMovement $movement): void
    {
        $this->handleChange($movement);
    }

    public function updated(MoneyMovement $movement): void
    {
        $this->handleChange($movement);
    }

    public function deleted(MoneyMovement $movement): void
    {
        $this->handleChange($movement);
    }

    protected function handleChange(MoneyMovement $movement): void
    {
        // Clear MoneyAgent cache for this organization
        AddyCacheManager::clearAgent('MoneyAgent', $movement->organization_id);
        
        // Also clear decision loop cache (cross-section insights might change)
        AddyCacheManager::clearOrganization($movement->organization_id);
        
        // Regenerate insights with fresh data (queue it to avoid blocking)
        try {
            $organization = Organization::find($movement->organization_id);
            if ($organization) {
                // Use queue to avoid blocking the request
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

