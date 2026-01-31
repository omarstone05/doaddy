<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Initialize default account types for an organization
     */
    public function initializeDefaultAccountTypes(string $organizationId): void
    {
        $defaults = AccountType::getDefaults();

        foreach ($defaults as $default) {
            AccountType::firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'code' => $default['code'],
                ],
                array_merge($default, [
                    'organization_id' => $organizationId,
                    'is_active' => true,
                ])
            );
        }
    }

    /**
     * Get account balance as of a specific date
     */
    public function getAccountBalance(Account $account, \Carbon\Carbon $asOf = null): float
    {
        return $account->calculateBalance($asOf);
    }

    /**
     * Update account current balance
     */
    public function updateAccountBalance(Account $account): void
    {
        $balance = $account->calculateBalance();
        $account->update(['current_balance' => $balance]);
    }

    /**
     * Recalculate all account balances for an organization
     */
    public function recalculateAllBalances(string $organizationId): void
    {
        $accounts = Account::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->get();

        foreach ($accounts as $account) {
            $this->updateAccountBalance($account);
        }
    }
}

