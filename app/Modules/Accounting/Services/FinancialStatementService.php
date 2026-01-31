<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class FinancialStatementService
{
    /**
     * Generate Trial Balance
     */
    public function generateTrialBalance(string $organizationId, Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();

        $accounts = Account::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->with('accountType')
            ->get();

        $trialBalance = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $balance = $account->calculateBalance($asOf);
            
            $debit = 0;
            $credit = 0;

            if ($account->normal_balance === 'debit') {
                $debit = $balance >= 0 ? $balance : 0;
                $credit = $balance < 0 ? abs($balance) : 0;
            } else {
                $credit = $balance >= 0 ? $balance : 0;
                $debit = $balance < 0 ? abs($balance) : 0;
            }

            $trialBalance[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->accountType->name,
                'debit' => $debit,
                'credit' => $credit,
            ];

            $totalDebits += $debit;
            $totalCredits += $credit;
        }

        return [
            'as_of' => $asOf->format('Y-m-d'),
            'accounts' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
        ];
    }

    /**
     * Generate Balance Sheet
     */
    public function generateBalanceSheet(string $organizationId, Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();

        $assets = $this->getAccountsByCategory($organizationId, 'asset', $asOf);
        $liabilities = $this->getAccountsByCategory($organizationId, 'liability', $asOf);
        $equity = $this->getAccountsByCategory($organizationId, 'equity', $asOf);

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');

        return [
            'as_of' => $asOf->format('Y-m-d'),
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Generate Income Statement
     */
    public function generateIncomeStatement(string $organizationId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $revenue = $this->getAccountsByCategory($organizationId, 'revenue', $periodEnd, $periodStart);
        $expenses = $this->getAccountsByCategory($organizationId, 'expense', $periodEnd, $periodStart);

        $totalRevenue = $revenue->sum('balance');
        $totalExpenses = $expenses->sum('balance');
        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ];
    }

    /**
     * Get accounts by category
     */
    protected function getAccountsByCategory(string $organizationId, string $category, Carbon $asOf, Carbon $from = null): Collection
    {
        $accountType = AccountType::where('organization_id', $organizationId)
            ->where('category', $category)
            ->where('is_active', true)
            ->first();

        if (!$accountType) {
            return collect([]);
        }

        $accounts = Account::where('organization_id', $organizationId)
            ->where('account_type_id', $accountType->id)
            ->where('is_active', true)
            ->get();

        return $accounts->map(function ($account) use ($asOf, $from) {
            $balance = $account->calculateBalance($asOf);
            
            if ($from) {
                $openingBalance = $account->calculateBalance($from->copy()->subDay());
                $balance = $balance - $openingBalance; // Period balance
            }

            return [
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];
        })->filter(function ($item) {
            return abs($item['balance']) > 0.01; // Only show accounts with activity
        });
    }
}

