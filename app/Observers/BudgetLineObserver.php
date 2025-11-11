<?php

namespace App\Observers;

use App\Models\BudgetLine;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Log;

class BudgetLineObserver
{
    public function created(BudgetLine $budget): void
    {
        $this->handleChange($budget);
    }

    public function updated(BudgetLine $budget): void
    {
        $this->handleChange($budget);
    }

    public function deleted(BudgetLine $budget): void
    {
        $this->handleChange($budget);
    }

    protected function handleChange(BudgetLine $budget): void
    {
        AddyCacheManager::clearAgent('MoneyAgent', $budget->organization_id);
        AddyCacheManager::clearOrganization($budget->organization_id);
        
        // Regenerate insights with fresh data
        try {
            $organization = Organization::find($budget->organization_id);
            if ($organization) {
                \App\Jobs\RegenerateAddyInsights::dispatch($organization)->delay(now()->addSeconds(5));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue insight regeneration', [
                'error' => $e->getMessage(),
                'organization_id' => $budget->organization_id,
            ]);
        }
    }
}

