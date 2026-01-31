<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Services\FinancialStatementService;
use App\Support\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class AccountingReportController extends Controller
{
    protected $financialStatementService;
    protected ModuleManager $moduleManager;

    public function __construct(FinancialStatementService $financialStatementService, ModuleManager $moduleManager)
    {
        $this->financialStatementService = $financialStatementService;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Check if Accounting module is enabled
     */
    protected function checkModuleEnabled()
    {
        if (!$this->moduleManager->isEnabled('Accounting')) {
            abort(403, 'The Accounting module is not enabled for your organization.');
        }
    }

    protected function getOrganizationId(): ?string
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $currentOrgId = session('current_organization_id');
        if ($currentOrgId) {
            $org = $user->organizations()->where('organizations.id', $currentOrgId)->first();
            if ($org) {
                return $org->id;
            }
        }
        
        return $user->organizations()->first()?->id;
    }

    /**
     * Display Trial Balance report
     */
    public function trialBalance(Request $request)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $asOf = $request->has('as_of') && $request->as_of 
            ? Carbon::parse($request->as_of) 
            : now();

        $trialBalance = $this->financialStatementService->generateTrialBalance($organizationId, $asOf);

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'trialBalance' => $trialBalance,
            'asOf' => $asOf->format('Y-m-d'),
        ]);
    }

    /**
     * Display General Ledger report
     */
    public function generalLedger(Request $request)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $fromDate = $request->has('from_date') && $request->from_date 
            ? Carbon::parse($request->from_date) 
            : now()->startOfMonth();
        
        $toDate = $request->has('to_date') && $request->to_date 
            ? Carbon::parse($request->to_date) 
            : now()->endOfMonth();

        // Get all accounts with their journal entries
        $accounts = \App\Modules\Accounting\Models\Account::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->with(['accountType'])
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($fromDate, $toDate) {
                $entries = $account->journalEntryLines()
                    ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                        $q->where('status', 'posted')
                          ->whereBetween('entry_date', [$fromDate, $toDate]);
                    })
                    ->with(['journalEntry'])
                    ->orderBy('created_at')
                    ->get();

                $openingBalance = $account->calculateBalance($fromDate->copy()->subDay());
                $closingBalance = $account->calculateBalance($toDate);

                return [
                    'account' => [
                        'id' => $account->id,
                        'code' => $account->code,
                        'name' => $account->name,
                        'account_type' => $account->accountType->name,
                    ],
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'entries' => $entries->map(function ($entry) {
                        return [
                            'date' => $entry->journalEntry->entry_date->format('Y-m-d'),
                            'entry_number' => $entry->journalEntry->entry_number,
                            'description' => $entry->description ?? $entry->journalEntry->description,
                            'reference' => $entry->reference ?? $entry->journalEntry->reference,
                            'type' => $entry->type,
                            'amount' => $entry->amount,
                        ];
                    }),
                ];
            })
            ->filter(function ($item) {
                return $item['entries']->count() > 0 || abs($item['opening_balance']) > 0.01;
            });

        return Inertia::render('Accounting/Reports/GeneralLedger', [
            'accounts' => $accounts,
            'fromDate' => $fromDate->format('Y-m-d'),
            'toDate' => $toDate->format('Y-m-d'),
        ]);
    }

    /**
     * Display Balance Sheet report
     */
    public function balanceSheet(Request $request)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $asOf = $request->has('as_of') && $request->as_of 
            ? Carbon::parse($request->as_of) 
            : now();

        $balanceSheet = $this->financialStatementService->generateBalanceSheet($organizationId, $asOf);

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'balanceSheet' => $balanceSheet,
            'asOf' => $asOf->format('Y-m-d'),
        ]);
    }

    /**
     * Display Income Statement report
     */
    public function incomeStatement(Request $request)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $periodStart = $request->has('period_start') && $request->period_start 
            ? Carbon::parse($request->period_start) 
            : now()->startOfMonth();
        
        $periodEnd = $request->has('period_end') && $request->period_end 
            ? Carbon::parse($request->period_end) 
            : now()->endOfMonth();

        $incomeStatement = $this->financialStatementService->generateIncomeStatement(
            $organizationId, 
            $periodStart, 
            $periodEnd
        );

        return Inertia::render('Accounting/Reports/IncomeStatement', [
            'incomeStatement' => $incomeStatement,
            'periodStart' => $periodStart->format('Y-m-d'),
            'periodEnd' => $periodEnd->format('Y-m-d'),
        ]);
    }

    /**
     * Display Cash Flow Statement report
     */
    public function cashFlow(Request $request)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $periodStart = $request->has('period_start') && $request->period_start 
            ? Carbon::parse($request->period_start) 
            : now()->startOfMonth();
        
        $periodEnd = $request->has('period_end') && $request->period_end 
            ? Carbon::parse($request->period_end) 
            : now()->endOfMonth();

        // Get cash accounts
        $cashAccounts = \App\Modules\Accounting\Models\Account::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->whereHas('accountType', function ($q) {
                $q->where('category', 'asset')
                  ->where('name', 'like', '%cash%');
            })
            ->get();

        // Calculate cash flow from operations, investing, and financing
        // This is a simplified version - you may want to enhance this
        $cashFlow = [
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'cash_accounts' => $cashAccounts->map(function ($account) use ($periodStart, $periodEnd) {
                $openingBalance = $account->calculateBalance($periodStart->copy()->subDay());
                $closingBalance = $account->calculateBalance($periodEnd);
                
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'net_change' => $closingBalance - $openingBalance,
                ];
            }),
            'net_cash_flow' => $cashAccounts->sum(function ($account) use ($periodStart, $periodEnd) {
                $openingBalance = $account->calculateBalance($periodStart->copy()->subDay());
                $closingBalance = $account->calculateBalance($periodEnd);
                return $closingBalance - $openingBalance;
            }),
        ];

        return Inertia::render('Accounting/Reports/CashFlow', [
            'cashFlow' => $cashFlow,
            'periodStart' => $periodStart->format('Y-m-d'),
            'periodEnd' => $periodEnd->format('Y-m-d'),
        ]);
    }
}

