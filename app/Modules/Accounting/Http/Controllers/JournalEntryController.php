<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\JournalEntryService;
use App\Support\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JournalEntryController extends Controller
{
    protected $journalEntryService;
    protected ModuleManager $moduleManager;

    public function __construct(JournalEntryService $journalEntryService, ModuleManager $moduleManager)
    {
        $this->journalEntryService = $journalEntryService;
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
     * Display a listing of journal entries
     */
    public function index(Request $request)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $query = JournalEntry::where('organization_id', $organizationId)
            ->with(['postedBy'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('entry_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('entry_date', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $journalEntries = $query->paginate(20);

        return Inertia::render('Accounting/JournalEntries/Index', [
            'journalEntries' => $journalEntries,
            'filters' => $request->only(['search', 'status', 'from_date', 'to_date']),
        ]);
    }

    /**
     * Show the form for creating a new journal entry
     */
    public function create()
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $accounts = Account::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('allows_postings', true)
            ->with('accountType')
            ->orderBy('code')
            ->get();

        return Inertia::render('Accounting/JournalEntries/Create', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a newly created journal entry
     */
    public function store(Request $request)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found.']);
        }

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:255',
            'type' => 'nullable|in:manual,system,recurring,adjusting,closing',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|uuid|exists:accounts,id',
            'lines.*.type' => 'required|in:debit,credit',
            'lines.*.amount' => 'required|numeric|min:0.01',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.reference' => 'nullable|string|max:255',
        ]);

        // Validate that debits equal credits
        $debits = collect($validated['lines'])->where('type', 'debit')->sum('amount');
        $credits = collect($validated['lines'])->where('type', 'credit')->sum('amount');

        if (abs($debits - $credits) > 0.01) {
            return back()->withErrors(['lines' => 'Journal entry must be balanced. Total debits must equal total credits.']);
        }

        DB::beginTransaction();
        try {
            $journalEntry = JournalEntry::create([
                'organization_id' => $organizationId,
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'],
                'reference' => $validated['reference'] ?? null,
                'type' => $validated['type'] ?? 'manual',
                'status' => 'draft',
            ]);

            foreach ($validated['lines'] as $index => $line) {
                $journalEntry->lines()->create([
                    'organization_id' => $organizationId,
                    'account_id' => $line['account_id'],
                    'type' => $line['type'],
                    'amount' => $line['amount'],
                    'description' => $line['description'] ?? null,
                    'reference' => $line['reference'] ?? null,
                    'line_number' => $index + 1,
                ]);
            }

            DB::commit();

            return redirect()->route('accounting.journal-entries.show', $journalEntry->id)
                ->with('message', 'Journal entry created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create journal entry', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to create journal entry: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified journal entry
     */
    public function show($id)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $journalEntry = JournalEntry::where('organization_id', $organizationId)
            ->with(['lines.account.accountType', 'postedBy', 'reversingEntry'])
            ->findOrFail($id);

        // Format the journal entry for display
        $journalEntry->lines = $journalEntry->lines->map(function ($line) {
            return [
                'id' => $line->id,
                'account_id' => $line->account_id,
                'account' => $line->account ? [
                    'id' => $line->account->id,
                    'code' => $line->account->code,
                    'name' => $line->account->name,
                    'account_type' => $line->account->accountType ? [
                        'name' => $line->account->accountType->name,
                    ] : null,
                ] : null,
                'type' => $line->type,
                'amount' => $line->amount,
                'description' => $line->description,
                'reference' => $line->reference,
                'line_number' => $line->line_number,
            ];
        });

        return Inertia::render('Accounting/JournalEntries/Show', [
            'journalEntry' => $journalEntry,
        ]);
    }

    /**
     * Post a journal entry
     */
    public function post($id)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found.']);
        }

        $journalEntry = JournalEntry::where('organization_id', $organizationId)
            ->with('lines')
            ->findOrFail($id);

        try {
            $this->journalEntryService->post($journalEntry);

            return redirect()->route('accounting.journal-entries.show', $journalEntry->id)
                ->with('message', 'Journal entry posted successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to post journal entry', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to post journal entry: ' . $e->getMessage()]);
        }
    }

    /**
     * Reverse a posted journal entry
     */
    public function reverse(Request $request, $id)
    {
        $this->checkModuleEnabled();
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found.']);
        }

        $journalEntry = JournalEntry::where('organization_id', $organizationId)
            ->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $reversal = $this->journalEntryService->reverse($journalEntry, $validated['reason'] ?? null);

            return redirect()->route('accounting.journal-entries.show', $reversal->id)
                ->with('message', 'Journal entry reversed successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to reverse journal entry', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to reverse journal entry: ' . $e->getMessage()]);
        }
    }
}

