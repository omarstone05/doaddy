<?php

namespace App\Http\Controllers;

use App\Models\BudgetLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BudgetLineController extends Controller
{
    public function index(Request $request)
    {
        $budgets = BudgetLine::where('organization_id', Auth::user()->organization_id)
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function ($budget) {
                return [
                    'id' => $budget->id,
                    'name' => $budget->name,
                    'category' => $budget->category,
                    'amount' => $budget->amount,
                    'spent' => $budget->spent,
                    'remaining' => $budget->remaining,
                    'percentage_spent' => $budget->percentage_spent,
                    'period' => $budget->period,
                    'start_date' => $budget->start_date,
                    'end_date' => $budget->end_date,
                ];
            });

        return Inertia::render('Money/Budgets/Index', [
            'budgets' => $budgets,
        ]);
    }

    public function create()
    {
        return Inertia::render('Money/Budgets/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'period' => 'required|in:monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $budget = BudgetLine::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'name' => $validated['name'],
            'category' => $validated['category'] ?? null,
            'amount' => $validated['amount'],
            'period' => $validated['period'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('money.budgets.index')->with('message', 'Budget created successfully');
    }

    public function edit($id)
    {
        $budget = BudgetLine::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        return Inertia::render('Money/Budgets/Edit', [
            'budget' => $budget,
        ]);
    }

    public function update(Request $request, $id)
    {
        $budget = BudgetLine::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'period' => 'required|in:monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $budget->update($validated);

        return redirect()->route('money.budgets.index')->with('message', 'Budget updated successfully');
    }

    public function destroy($id)
    {
        BudgetLine::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id)
            ->delete();

        return redirect()->route('money.budgets.index')->with('message', 'Budget deleted successfully');
    }
}
