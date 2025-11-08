<?php

namespace App\Http\Controllers;

use App\Models\BusinessValuation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BusinessValuationController extends Controller
{
    public function index(Request $request)
    {
        $valuations = BusinessValuation::where('organization_id', Auth::user()->organization_id)
            ->with(['valuedBy', 'createdBy'])
            ->orderBy('valuation_date', 'desc')
            ->paginate(20);

        return Inertia::render('Decisions/Valuation/Index', [
            'valuations' => $valuations,
        ]);
    }

    public function create()
    {
        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Decisions/Valuation/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'valuation_date' => 'required|date',
            'valuation_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'valuation_method' => 'required|in:revenue_multiple,ebitda_multiple,asset_based,discounted_cash_flow,market_comparable,other',
            'method_details' => 'nullable|string',
            'assumptions' => 'nullable|string',
            'notes' => 'nullable|string',
            'valued_by_id' => 'nullable|uuid|exists:users,id',
        ]);

        $valuation = BusinessValuation::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'created_by_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()->route('decisions.valuation.index')->with('message', 'Business valuation created successfully');
    }

    public function show($id)
    {
        $valuation = BusinessValuation::where('organization_id', Auth::user()->organization_id)
            ->with(['valuedBy', 'createdBy'])
            ->findOrFail($id);

        return Inertia::render('Decisions/Valuation/Show', [
            'valuation' => $valuation,
        ]);
    }

    public function edit($id)
    {
        $valuation = BusinessValuation::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Decisions/Valuation/Edit', [
            'valuation' => $valuation,
            'users' => $users,
        ]);
    }

    public function update(Request $request, $id)
    {
        $valuation = BusinessValuation::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'valuation_date' => 'required|date',
            'valuation_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'valuation_method' => 'required|in:revenue_multiple,ebitda_multiple,asset_based,discounted_cash_flow,market_comparable,other',
            'method_details' => 'nullable|string',
            'assumptions' => 'nullable|string',
            'notes' => 'nullable|string',
            'valued_by_id' => 'nullable|uuid|exists:users,id',
        ]);

        $valuation->update($validated);

        return redirect()->route('decisions.valuation.index')->with('message', 'Business valuation updated successfully');
    }

    public function destroy($id)
    {
        $valuation = BusinessValuation::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $valuation->delete();

        return redirect()->route('decisions.valuation.index')->with('message', 'Business valuation deleted successfully');
    }
}

