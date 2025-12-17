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

class IncomeController extends Controller
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
     * Display a listing of income transactions
     */
    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $query = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->with(['toAccount', 'createdBy', 'attachments'])
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

        $incomes = $query->paginate(20);

        // Get stats
        $totalIncome = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->sum('amount') ?? 0;

        $thisMonthIncome = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount') ?? 0;

        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Money/Income/Index', [
            'incomes' => $incomes,
            'accounts' => $accounts,
            'stats' => [
                'total_income' => $totalIncome,
                'this_month_income' => $thisMonthIncome,
                'total_count' => $incomes->total(),
            ],
            'filters' => $request->only(['from_date', 'to_date', 'category', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new income
     */
    public function create()
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Money/Income/Create', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a newly created income
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
            'to_account_id' => 'required|uuid|exists:money_accounts,id',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ]);

        // Validate account belongs to organization
        MoneyAccount::where('organization_id', $organizationId)
            ->findOrFail($validated['to_account_id']);

        DB::beginTransaction();
        try {
            $income = MoneyMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'flow_type' => 'income',
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'ZMW',
                'transaction_date' => $validated['transaction_date'],
                'to_account_id' => $validated['to_account_id'],
                'description' => $validated['description'],
                'category' => $validated['category'] ?? null,
                'status' => 'approved',
                'created_by_id' => Auth::id(),
            ]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $uploadedFile = $fileManager->upload($file, Auth::user(), 'income', $organizationId);
                    
                    // Store file path
                    $filePath = $uploadedFile->storage_driver === 'google' 
                        ? $uploadedFile->storage_path 
                        : Storage::disk('public')->url($uploadedFile->storage_path);
                    
                    Attachment::create([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $organizationId,
                        'attachable_type' => MoneyMovement::class,
                        'attachable_id' => $income->id,
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

            return redirect()->route('income.index')->with('message', 'Income recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to record income', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to record income: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified income
     */
    public function show($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $income = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->with(['toAccount', 'createdBy', 'attachments.uploadedBy'])
            ->findOrFail($id);

        return Inertia::render('Money/Income/Show', [
            'income' => $income,
        ]);
    }

    /**
     * Show the form for editing the specified income
     */
    public function edit($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $income = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->with('attachments')
            ->findOrFail($id);

        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Money/Income/Edit', [
            'income' => $income,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update the specified income
     */
    public function update(Request $request, $id, FileManager $fileManager)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $income = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'transaction_date' => 'required|date',
            'to_account_id' => 'required|uuid|exists:money_accounts,id',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ]);

        // Validate account belongs to organization
        MoneyAccount::where('organization_id', $organizationId)
            ->findOrFail($validated['to_account_id']);

        DB::beginTransaction();
        try {
            // Update income
            $income->update([
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'ZMW',
                'transaction_date' => $validated['transaction_date'],
                'to_account_id' => $validated['to_account_id'],
                'description' => $validated['description'],
                'category' => $validated['category'] ?? null,
            ]);

            // Handle new file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $uploadedFile = $fileManager->upload($file, Auth::user(), 'income', $organizationId);
                    
                    // Store file path
                    $filePath = $uploadedFile->storage_driver === 'google' 
                        ? $uploadedFile->storage_path 
                        : Storage::disk('public')->url($uploadedFile->storage_path);
                    
                    Attachment::create([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $organizationId,
                        'attachable_type' => MoneyMovement::class,
                        'attachable_id' => $income->id,
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

            return redirect()->route('income.show', $income->id)->with('message', 'Income updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update income', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to update income: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified income
     */
    public function destroy($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $income = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete attachments
            $income->attachments()->delete();
            
            // Delete income
            $income->delete();

            DB::commit();

            return redirect()->route('income.index')->with('message', 'Income deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete income', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to delete income: ' . $e->getMessage()]);
        }
    }
}
