<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use App\Support\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccountController extends Controller
{
    protected ModuleManager $moduleManager;

    public function __construct(ModuleManager $moduleManager)
    {
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
     * Ensure default accounts exist for the organization
     * This runs automatically when an organization first accesses the Chart of Accounts
     */
    protected function ensureDefaultAccountsExist(string $organizationId): void
    {
        // Check if accounts already exist for this organization
        $accountCount = Account::where('organization_id', $organizationId)->count();
        
        if ($accountCount === 0) {
            // No accounts exist, seed default accounts
            try {
                \Log::info('Auto-seeding default accounts for organization', [
                    'organization_id' => $organizationId,
                ]);
                
                $seeder = new \App\Modules\Accounting\Database\Seeders\DefaultChartOfAccountsSeeder();
                $seeder->setOrganizationId($organizationId);
                $seeder->run();
                
                \Log::info('Default accounts seeded successfully', [
                    'organization_id' => $organizationId,
                    'accounts_created' => Account::where('organization_id', $organizationId)->count(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to auto-seed default accounts', [
                    'organization_id' => $organizationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Don't throw - let the page load even if seeding fails
            }
        }
    }

    /**
     * Display a listing of accounts
     */
    public function index(Request $request)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        // Auto-seed default accounts if none exist for this organization
        $this->ensureDefaultAccountsExist($organizationId);

        $query = Account::where('organization_id', $organizationId)
            ->with(['accountType', 'parentAccount'])
            ->orderBy('code');

        // Filter by account type enum (for tab-based filtering)
        if ($request->has('account_type') && $request->account_type) {
            $query->where('account_type', $request->account_type);
        }

        // Filter by account type ID (legacy support)
        if ($request->has('account_type_id') && $request->account_type_id) {
            $query->where('account_type_id', $request->account_type_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        } else {
            $query->where('is_active', true);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $accounts = $query->get();

        \Log::info('AccountController::index', [
            'organization_id' => $organizationId,
            'accounts_count' => $accounts->count(),
            'account_type_ids' => $accounts->pluck('account_type_id')->unique()->values()->toArray(),
        ]);

        // Group by account_type_id to get proper AccountType information
        $groupedAccounts = $accounts->groupBy('account_type_id')->map(function ($typeAccounts, $accountTypeId) use ($organizationId) {
            // Get the AccountType for this group - try with organization_id first, then without
            $accountType = AccountType::where('organization_id', $organizationId)
                ->where('id', $accountTypeId)
                ->first();
            
            // If not found, try without organization_id constraint (in case accounts were seeded for different org)
            if (!$accountType) {
                $accountType = AccountType::where('id', $accountTypeId)->first();
            }
            
            \Log::info('Grouping accounts', [
                'account_type_id' => $accountTypeId,
                'accounts_count' => $typeAccounts->count(),
                'account_type_found' => $accountType ? $accountType->name : 'NOT FOUND',
                'account_type_org_id' => $accountType ? $accountType->organization_id : null,
            ]);
            
            // Fallback if account type still not found
            if (!$accountType) {
                $firstAccount = $typeAccounts->first();
                $accountType = AccountType::where('organization_id', $organizationId)
                    ->where('category', $firstAccount->account_type ?? 'asset')
                    ->first();
                
                // Last resort: find any AccountType with matching category
                if (!$accountType && $firstAccount) {
                    $accountType = AccountType::where('category', $firstAccount->account_type ?? 'asset')
                        ->first();
                }
                
                \Log::warning('AccountType not found by ID, trying fallback', [
                    'account_type_id' => $accountTypeId,
                    'fallback_category' => $firstAccount->account_type ?? 'asset',
                    'fallback_found' => $accountType ? $accountType->name : 'NOT FOUND',
                ]);
            }
            
            // Create a fallback type if AccountType not found
            $typeData = null;
            if ($accountType) {
                $typeData = [
                    'id' => $accountType->id,
                    'name' => $accountType->name,
                    'description' => $accountType->description,
                    'code' => $accountType->code,
                    'category' => $accountType->category,
                ];
            } else {
                // Fallback: create a generic type from the account's account_type enum
                $firstAccount = $typeAccounts->first();
                $accountTypeEnum = $firstAccount->account_type ?? 'asset';
                $typeName = ucfirst(str_replace('_', ' ', $accountTypeEnum));
                $typeData = [
                    'id' => $accountTypeId,
                    'name' => $typeName,
                    'description' => "Accounts of type {$typeName}",
                    'code' => strtoupper(substr($accountTypeEnum, 0, 4)),
                    'category' => $accountTypeEnum,
                ];
                \Log::warning('Using fallback type data for accounts', [
                    'account_type_id' => $accountTypeId,
                    'fallback_type' => $typeName,
                ]);
            }
            
            return [
                'type' => $typeData,
                'account_type' => $typeAccounts->first()->account_type ?? null,
                'accounts' => $typeAccounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'code' => $account->code,
                        'name' => $account->name,
                        'description' => $account->description,
                        'current_balance' => $account->current_balance,
                        'normal_balance' => $account->normal_balance,
                        'is_sub_account' => $account->is_sub_account,
                        'is_system_account' => $account->is_system_account,
                        'last_transaction_date' => $account->getLastTransactionDate()?->format('Y-m-d'),
                        'parent_account' => $account->parentAccount ? [
                            'id' => $account->parentAccount->id,
                            'code' => $account->parentAccount->code,
                            'name' => $account->parentAccount->name,
                        ] : null,
                        'sub_accounts_count' => $account->subAccounts()->count(),
                        'can_delete' => $account->canDelete(),
                    ];
                }),
            ];
        })->filter(function ($group) {
            // Only filter out groups that have no accounts (shouldn't happen, but just in case)
            return count($group['accounts'] ?? []) > 0;
        });
        
        \Log::info('AccountController::index - groupedAccounts', [
            'groups_count' => $groupedAccounts->count(),
            'groups' => $groupedAccounts->map(function ($group) {
                return [
                    'type_name' => $group['type']['name'] ?? 'NO TYPE',
                    'accounts_count' => count($group['accounts'] ?? []),
                ];
            })->toArray(),
        ]);

        // Get account counts by type for tabs
        $accountCounts = [
            'asset' => Account::where('organization_id', $organizationId)->where('account_type', 'asset')->where('is_active', true)->count(),
            'liability' => Account::where('organization_id', $organizationId)->where('account_type', 'liability')->where('is_active', true)->count(),
            'equity' => Account::where('organization_id', $organizationId)->where('account_type', 'equity')->where('is_active', true)->count(),
            'income' => Account::where('organization_id', $organizationId)->where('account_type', 'income')->where('is_active', true)->count(),
            'expense' => Account::where('organization_id', $organizationId)->where('account_type', 'expense')->where('is_active', true)->count(),
            'other_income' => Account::where('organization_id', $organizationId)->where('account_type', 'other_income')->where('is_active', true)->count(),
            'cost_of_goods_sold' => Account::where('organization_id', $organizationId)->where('account_type', 'cost_of_goods_sold')->where('is_active', true)->count(),
        ];

        $accountTypes = AccountType::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Accounting/Accounts/Index', [
            'groupedAccounts' => $groupedAccounts->values(),
            'accountTypes' => $accountTypes,
            'accountCounts' => $accountCounts,
            'filters' => $request->only(['search', 'account_type', 'account_type_id', 'is_active']),
        ]);
    }

    /**
     * Show the form for creating a new account
     */
    public function create()
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $accountTypes = AccountType::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $parentAccounts = Account::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('allows_postings', false) // Only parent accounts can have sub-accounts
            ->orderBy('code')
            ->get();

        return Inertia::render('Accounting/Accounts/Create', [
            'accountTypes' => $accountTypes,
            'parentAccounts' => $parentAccounts,
        ]);
    }

    /**
     * Store a newly created account
     */
    public function store(Request $request)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found.']);
        }

        $validated = $request->validate([
            'account_type_id' => 'required|uuid|exists:account_types,id',
            'parent_account_id' => 'nullable|uuid|exists:accounts,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'normal_balance' => 'required|in:debit,credit',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_sub_account' => 'boolean',
            'allows_postings' => 'boolean',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        // Check if code is unique within organization
        $existingAccount = Account::where('organization_id', $organizationId)
            ->where('code', $validated['code'])
            ->first();

        if ($existingAccount) {
            return back()->withErrors(['code' => 'Account code must be unique.']);
        }

        // Get account type to set normal balance if not provided
        $accountType = AccountType::find($validated['account_type_id']);
        if (!$accountType) {
            return back()->withErrors(['account_type_id' => 'Invalid account type.']);
        }

        // Set normal balance from account type if not provided
        if (!isset($validated['normal_balance'])) {
            $validated['normal_balance'] = $accountType->normal_balance;
        }

        // Determine if this is a sub-account
        $validated['is_sub_account'] = isset($validated['parent_account_id']) && $validated['parent_account_id'];

        // Calculate level
        $level = 1;
        if ($validated['is_sub_account']) {
            $parent = Account::find($validated['parent_account_id']);
            $level = $parent->level + 1;
        }
        $validated['level'] = $level;

        DB::beginTransaction();
        try {
            $account = Account::create([
                'organization_id' => $organizationId,
                'account_type_id' => $validated['account_type_id'],
                'parent_account_id' => $validated['parent_account_id'] ?? null,
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'normal_balance' => $validated['normal_balance'],
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'current_balance' => $validated['opening_balance'] ?? 0,
                'is_sub_account' => $validated['is_sub_account'],
                'allows_postings' => $validated['allows_postings'] ?? true,
                'level' => $level,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            return redirect()->route('accounting.accounts.show', $account->id)
                ->with('message', 'Account created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to create account: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified account
     */
    public function show($id)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $account = Account::where('organization_id', $organizationId)
            ->with(['accountType', 'parentAccount', 'subAccounts.accountType'])
            ->findOrFail($id);

        // Get recent journal entry lines
        $recentEntries = $account->journalEntryLines()
            ->with(['journalEntry'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($line) {
                return [
                    'id' => $line->id,
                    'journal_entry' => $line->journalEntry ? [
                        'id' => $line->journalEntry->id,
                        'entry_number' => $line->journalEntry->entry_number,
                        'entry_date' => $line->journalEntry->entry_date,
                        'description' => $line->journalEntry->description,
                    ] : null,
                    'type' => $line->type,
                    'amount' => $line->amount,
                    'description' => $line->description,
                    'reference' => $line->reference,
                ];
            });

        // Calculate balance history (last 12 months)
        $balanceHistory = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i)->endOfMonth();
            $balanceHistory[] = [
                'month' => $date->format('M Y'),
                'balance' => $account->calculateBalance($date),
            ];
        }

        return Inertia::render('Accounting/Accounts/Show', [
            'account' => $account,
            'recentEntries' => $recentEntries,
            'balanceHistory' => $balanceHistory,
        ]);
    }

    /**
     * Show the form for editing the specified account
     */
    public function edit($id)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $account = Account::where('organization_id', $organizationId)
            ->findOrFail($id);

        $accountTypes = AccountType::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $parentAccounts = Account::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('id', '!=', $id) // Can't be its own parent
            ->where('allows_postings', false)
            ->orderBy('code')
            ->get();

        return Inertia::render('Accounting/Accounts/Edit', [
            'account' => $account,
            'accountTypes' => $accountTypes,
            'parentAccounts' => $parentAccounts,
        ]);
    }

    /**
     * Update the specified account
     */
    public function update(Request $request, $id)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found.']);
        }

        $account = Account::where('organization_id', $organizationId)
            ->findOrFail($id);

        $validated = $request->validate([
            'account_type_id' => 'required|uuid|exists:account_types,id',
            'parent_account_id' => 'nullable|uuid|exists:accounts,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'normal_balance' => 'required|in:debit,credit',
            'opening_balance' => 'nullable|numeric|min:0',
            'allows_postings' => 'boolean',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        // Check if code is unique (excluding current account)
        $existingAccount = Account::where('organization_id', $organizationId)
            ->where('code', $validated['code'])
            ->where('id', '!=', $id)
            ->first();

        if ($existingAccount) {
            return back()->withErrors(['code' => 'Account code must be unique.']);
        }

        // Prevent setting account as its own parent
        if ($validated['parent_account_id'] == $id) {
            return back()->withErrors(['parent_account_id' => 'Account cannot be its own parent.']);
        }

        // Determine if this is a sub-account
        $validated['is_sub_account'] = isset($validated['parent_account_id']) && $validated['parent_account_id'];

        // Calculate level
        $level = 1;
        if ($validated['is_sub_account']) {
            $parent = Account::find($validated['parent_account_id']);
            $level = $parent->level + 1;
        }
        $validated['level'] = $level;

        DB::beginTransaction();
        try {
            $account->update([
                'account_type_id' => $validated['account_type_id'],
                'parent_account_id' => $validated['parent_account_id'] ?? null,
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'normal_balance' => $validated['normal_balance'],
                'opening_balance' => $validated['opening_balance'] ?? $account->opening_balance,
                'is_sub_account' => $validated['is_sub_account'],
                'allows_postings' => $validated['allows_postings'] ?? $account->allows_postings,
                'level' => $level,
                'sort_order' => $validated['sort_order'] ?? $account->sort_order,
                'is_active' => $validated['is_active'] ?? $account->is_active,
            ]);

            DB::commit();

            return redirect()->route('accounting.accounts.show', $account->id)
                ->with('message', 'Account updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to update account: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified account
     */
    public function destroy($id)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found.']);
        }

        $account = Account::where('organization_id', $organizationId)
            ->findOrFail($id);

        // Use the model's canDelete method
        if (!$account->canDelete()) {
            if ($account->is_system_account) {
                return back()->withErrors(['error' => 'System accounts cannot be deleted.']);
            }
            if ($account->hasTransactions()) {
                return back()->withErrors(['error' => 'Cannot delete account with transactions.']);
            }
            if (abs($account->current_balance) > 0.01) {
                return back()->withErrors(['error' => 'Cannot delete account with non-zero balance.']);
            }
            if ($account->subAccounts()->count() > 0) {
                return back()->withErrors(['error' => 'Cannot delete account with sub-accounts.']);
            }
            return back()->withErrors(['error' => 'Account cannot be deleted.']);
        }

        DB::beginTransaction();
        try {
            $account->delete();
            DB::commit();

            return redirect()->route('accounting.accounts.index')
                ->with('message', 'Account deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to delete account: ' . $e->getMessage()]);
        }
    }

    /**
     * Get accounts filtered by type for dropdowns
     */
    public function getByType(Request $request, $type)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return response()->json(['error' => 'No organization found.'], 400);
        }

        $accounts = Account::where('organization_id', $organizationId)
            ->where('account_type', $type)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'current_balance' => $account->current_balance,
                ];
            });

        return response()->json($accounts);
    }

    /**
     * Restore a soft-deleted account
     */
    public function restore($id)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found.']);
        }

        $account = Account::where('organization_id', $organizationId)
            ->withTrashed()
            ->findOrFail($id);

        if (!$account->trashed()) {
            return back()->withErrors(['error' => 'Account is not deleted.']);
        }

        DB::beginTransaction();
        try {
            $account->restore();
            DB::commit();

            return redirect()->route('accounting.accounts.show', $account->id)
                ->with('message', 'Account restored successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to restore account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to restore account: ' . $e->getMessage()]);
        }
    }
}

