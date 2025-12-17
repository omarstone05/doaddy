<?php

namespace App\Observers;

use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Log;

class LeaveRequestObserver
{
    public function created(LeaveRequest $leave): void
    {
        $this->handleChange($leave);
    }

    public function updated(LeaveRequest $leave): void
    {
        $this->handleChange($leave);
    }

    public function deleted(LeaveRequest $leave): void
    {
        $this->handleChange($leave);
    }

    protected function handleChange(LeaveRequest $leave): void
    {
        AddyCacheManager::clearAgent('PeopleAgent', $leave->organization_id);
        AddyCacheManager::clearOrganization($leave->organization_id);
        
        // Regenerate insights with fresh data
        try {
            $organization = Organization::find($leave->organization_id);
            if ($organization) {
                \App\Jobs\RegenerateAddyInsights::dispatch($organization)->delay(now()->addSeconds(5));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue insight regeneration', [
                'error' => $e->getMessage(),
                'organization_id' => $leave->organization_id,
            ]);
        }
    }
}

