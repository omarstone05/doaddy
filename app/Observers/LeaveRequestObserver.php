<?php

namespace App\Observers;

use App\Models\LeaveRequest;
use App\Services\Addy\AddyCacheManager;

class LeaveRequestObserver
{
    public function created(LeaveRequest $leave): void
    {
        $this->clearCache($leave);
    }

    public function updated(LeaveRequest $leave): void
    {
        $this->clearCache($leave);
    }

    public function deleted(LeaveRequest $leave): void
    {
        $this->clearCache($leave);
    }

    protected function clearCache(LeaveRequest $leave): void
    {
        AddyCacheManager::clearAgent('PeopleAgent', $leave->organization_id);
        AddyCacheManager::clearOrganization($leave->organization_id);
    }
}

