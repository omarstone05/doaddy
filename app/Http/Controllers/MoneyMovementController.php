<?php

namespace App\Http\Controllers;

use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MoneyMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = MoneyMovement::where('organization_id', Auth::user()->organization_id)
            ->with(['fromAccount', 'toAccount'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('flow_type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('transaction_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('transaction_date', '<=', $request->to_date);
        }

        $movements = $query->paginate(20);

        $accounts = MoneyAccount::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Money/Movements/Index', [
            'movements' => $movements,
            'accounts' => $accounts,
            'filters' => $request->only(['type', 'from_date', 'to_date']),
        ]);
    }

    public function create()
    {
        $accounts = MoneyAccount::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Money/Movements/Create', [
            'accounts' => $accounts,
            'type' => request()->query('type'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'flow_type' => 'sometimes|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'transaction_date' => 'required|date',
            'from_account_id' => 'required_if:flow_type,expense,transfer|nullable|uuid|exists:money_accounts,id',
            'to_account_id' => 'required_if:flow_type,income,transfer|nullable|uuid|exists:money_accounts,id',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
        ]);

        // Default to expense if not specified (for quick expense entry)
        if (!isset($validated['flow_type'])) {
            $validated['flow_type'] = 'expense';
        }
        if (!isset($validated['currency'])) {
            $validated['currency'] = 'ZMW';
        }
        
        // For quick expense, require from_account_id
        if ($validated['flow_type'] === 'expense' && !isset($validated['from_account_id'])) {
            if ($request->has('from_account_id')) {
                $validated['from_account_id'] = $request->input('from_account_id');
            } else {
                return back()->withErrors(['from_account_id' => 'Please select an account']);
            }
        }

        // Validate account belongs to organization
        if ($validated['from_account_id']) {
            MoneyAccount::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($validated['from_account_id']);
        }
        if ($validated['to_account_id']) {
            MoneyAccount::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($validated['to_account_id']);
        }

        $movement = MoneyMovement::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'flow_type' => $validated['flow_type'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'transaction_date' => $validated['transaction_date'],
            'from_account_id' => $validated['from_account_id'] ?? null,
            'to_account_id' => $validated['to_account_id'] ?? null,
            'description' => $validated['description'],
            'category' => $validated['category'] ?? null,
            'status' => 'approved',
            'created_by_id' => Auth::id(),
        ]);

        return redirect()->route('money.movements.index')->with('message', 'Movement recorded successfully');
    }

    public function show($id)
    {
        $movement = MoneyMovement::where('organization_id', Auth::user()->organization_id)
            ->with(['fromAccount', 'toAccount', 'createdBy', 'attachments.uploadedBy'])
            ->findOrFail($id);

        return Inertia::render('Money/Movements/Show', [
            'movement' => $movement,
        ]);
    }
}
