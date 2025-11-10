<?php

namespace App\Observers;

use App\Models\BudgetLine;
use App\Services\Addy\AddyCacheManager;

class BudgetLineObserver
{
    public function created(BudgetLine $budget): void
    {
        $this->clearCache($budget);
    }

    public function updated(BudgetLine $budget): void
    {
        $this->clearCache($budget);
    }

    public function deleted(BudgetLine $budget): void
    {
        $this->clearCache($budget);
    }

    protected function clearCache(BudgetLine $budget): void
    {
        AddyCacheManager::clearAgent('MoneyAgent', $budget->organization_id);
        AddyCacheManager::clearOrganization($budget->organization_id);
    }
}

