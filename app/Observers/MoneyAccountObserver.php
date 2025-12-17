<?php

namespace App\Observers;

use App\Models\MoneyAccount;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Log;

class MoneyAccountObserver
{
    public function created(MoneyAccount $account): void
    {
        $this->handleChange($account);
    }

    public function updated(MoneyAccount $account): void
    {
        $this->handleChange($account);
    }

    public function deleted(MoneyAccount $account): void
    {
        $this->handleChange($account);
    }

    protected function handleChange(MoneyAccount $account): void
    {
        // Clear MoneyAgent cache (accounts affect money insights)
        AddyCacheManager::clearAgent('MoneyAgent', $account->organization_id);
        AddyCacheManager::clearOrganization($account->organization_id);
        
        // Regenerate insights with fresh data
        try {
            $organization = Organization::find($account->organization_id);
            if ($organization) {
                \App\Jobs\RegenerateAddyInsights::dispatch($organization)->delay(now()->addSeconds(5));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue insight regeneration', [
                'error' => $e->getMessage(),
                'organization_id' => $account->organization_id,
            ]);
        }
    }
}

