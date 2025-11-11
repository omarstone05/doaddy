<?php

namespace App\Observers;

use App\Models\PayrollRun;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Log;

class PayrollRunObserver
{
    public function created(PayrollRun $payroll): void
    {
        $this->handleChange($payroll);
    }

    public function updated(PayrollRun $payroll): void
    {
        $this->handleChange($payroll);
    }

    public function deleted(PayrollRun $payroll): void
    {
        $this->handleChange($payroll);
    }

    protected function handleChange(PayrollRun $payroll): void
    {
        // Clear PeopleAgent cache (payroll affects people insights)
        AddyCacheManager::clearAgent('PeopleAgent', $payroll->organization_id);
        AddyCacheManager::clearOrganization($payroll->organization_id);
        
        // Regenerate insights with fresh data
        try {
            $organization = Organization::find($payroll->organization_id);
            if ($organization) {
                \App\Jobs\RegenerateAddyInsights::dispatch($organization)->delay(now()->addSeconds(5));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue insight regeneration', [
                'error' => $e->getMessage(),
                'organization_id' => $payroll->organization_id,
            ]);
        }
    }
}

