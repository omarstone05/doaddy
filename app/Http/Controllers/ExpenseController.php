<?php

namespace App\Http\Controllers;

use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Models\Attachment;
use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    /**
     * Helper to get the current organization ID
     */
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
        
        if ($user->attributes['organization_id'] ?? null) {
            $org = $user->organizations()->where('organizations.id', $user->attributes['organization_id'])->first();
            if ($org) {
                return $org->id;
            }
        }
        
        return $user->organizations()->first()?->id;
    }

    /**
     * Display a listing of expense transactions
     */
    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
            return redirect($pendaCloudUrl . '/onboarding/step-1');
        }

        $query = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->with(['fromAccount', 'createdBy', 'attachments'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('from_date') && $request->from_date) {
            $query->where('transaction_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('transaction_date', '<=', $request->to_date);
        }
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $expenses = $query->paginate(20);

        // Get stats
        $totalExpenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->sum('amount') ?? 0;

        $thisMonthExpenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount') ?? 0;

        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Money/Expenses/Index', [
            'expenses' => $expenses,
            'accounts' => $accounts,
            'stats' => [
                'total_expenses' => $totalExpenses,
                'this_month_expenses' => $thisMonthExpenses,
                'total_count' => $expenses->total(),
            ],
            'filters' => $request->only(['from_date', 'to_date', 'category', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new expense
     */
    public function create()
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
            return redirect($pendaCloudUrl . '/onboarding/step-1');
        }

        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Money/Expenses/Create', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request, FileManager $fileManager)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'transaction_date' => 'required|date',
            'from_account_id' => 'required|uuid|exists:money_accounts,id',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ]);

        // Validate account belongs to organization
        MoneyAccount::where('organization_id', $organizationId)
            ->findOrFail($validated['from_account_id']);

        DB::beginTransaction();
        try {
            $expense = MoneyMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'flow_type' => 'expense',
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'ZMW',
                'transaction_date' => $validated['transaction_date'],
                'from_account_id' => $validated['from_account_id'],
                'description' => $validated['description'],
                'category' => $validated['category'] ?? null,
                'status' => 'approved',
                'created_by_id' => Auth::id(),
            ]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $uploadedFile = $fileManager->upload($file, Auth::user(), 'expense', $organizationId);
                    
                    // Store file path
                    $filePath = $uploadedFile->storage_driver === 'google' 
                        ? $uploadedFile->storage_path 
                        : Storage::disk('public')->url($uploadedFile->storage_path);
                    
                    Attachment::create([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $organizationId,
                        'attachable_type' => MoneyMovement::class,
                        'attachable_id' => $expense->id,
                        'name' => $file->getClientOriginalName(),
                        'file_path' => $uploadedFile->storage_path,
                        'file_name' => $uploadedFile->file_name,
                        'file_size' => $uploadedFile->file_size,
                        'mime_type' => $uploadedFile->mime_type,
                        'url' => $filePath,
                        'uploaded_by_id' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            // Award XP for recording expense (after successful commit)
            if (config('features.gamification', true)) {
                try {
                    $gamificationService = app(\App\Services\Addy\GamificationService::class);
                    
                    // Determine XP reason - more XP if receipt attached
                    $xpReason = $request->hasFile('attachments') ? 'expense_with_receipt' : 'expense_recorded';
                    
                    // Bonus XP for categorizing
                    if (!empty($validated['category'])) {
                        $gamificationService->awardXP(
                            Auth::id(),
                            'expense_categorized_bonus',
                            $organizationId,
                            ['expense_id' => $expense->id]
                        );
                    }
                    
                    $gamificationService->awardXP(
                        Auth::id(),
                        $xpReason,
                        $organizationId,
                        ['expense_id' => $expense->id, 'amount' => $validated['amount']]
                    );
                } catch (\Exception $e) {
                    // Log but don't fail the expense recording
                    \Log::warning('Failed to award XP for expense', ['error' => $e->getMessage()]);
                }
            }

            return redirect()->route('money.expenses.index')->with('message', 'Expense recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to record expense', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to record expense: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified expense
     */
    public function show($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
            return redirect($pendaCloudUrl . '/onboarding/step-1');
        }

        $expense = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->with(['fromAccount', 'createdBy', 'attachments.uploadedBy'])
            ->findOrFail($id);

        return Inertia::render('Money/Expenses/Show', [
            'expense' => $expense,
        ]);
    }

    /**
     * Show the form for editing the specified expense
     */
    public function edit($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
            return redirect($pendaCloudUrl . '/onboarding/step-1');
        }

        $expense = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->with('attachments')
            ->findOrFail($id);

        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Money/Expenses/Edit', [
            'expense' => $expense,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, $id, FileManager $fileManager)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $expense = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'transaction_date' => 'required|date',
            'from_account_id' => 'required|uuid|exists:money_accounts,id',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ]);

        // Validate account belongs to organization
        MoneyAccount::where('organization_id', $organizationId)
            ->findOrFail($validated['from_account_id']);

        DB::beginTransaction();
        try {
            // Update expense
            $expense->update([
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'ZMW',
                'transaction_date' => $validated['transaction_date'],
                'from_account_id' => $validated['from_account_id'],
                'description' => $validated['description'],
                'category' => $validated['category'] ?? null,
            ]);

            // Handle new file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $uploadedFile = $fileManager->upload($file, Auth::user(), 'expense', $organizationId);
                    
                    // Store file path
                    $filePath = $uploadedFile->storage_driver === 'google' 
                        ? $uploadedFile->storage_path 
                        : Storage::disk('public')->url($uploadedFile->storage_path);
                    
                    Attachment::create([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $organizationId,
                        'attachable_type' => MoneyMovement::class,
                        'attachable_id' => $expense->id,
                        'name' => $file->getClientOriginalName(),
                        'file_path' => $uploadedFile->storage_path,
                        'file_name' => $uploadedFile->file_name,
                        'file_size' => $uploadedFile->file_size,
                        'mime_type' => $uploadedFile->mime_type,
                        'url' => $filePath,
                        'uploaded_by_id' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('money.expenses.show', $expense->id)->with('message', 'Expense updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update expense', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to update expense: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $expense = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete attachments
            $expense->attachments()->delete();
            
            // Delete expense
            $expense->delete();

            DB::commit();

            return redirect()->route('money.expenses.index')->with('message', 'Expense deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete expense', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to delete expense: ' . $e->getMessage()]);
        }
    }
}
