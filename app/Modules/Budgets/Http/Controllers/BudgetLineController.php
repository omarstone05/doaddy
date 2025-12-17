<?php

namespace App\Modules\Budgets\Http\Controllers;

use App\Services\Budget\BudgetServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BudgetLineController extends \App\Http\Controllers\Controller
{
    public function __construct(private BudgetServiceClient $budgetClient)
    {
    }

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

    public function index(Request $request)
    {
        // Check if module is enabled
        $moduleManager = app(\App\Support\ModuleManager::class);
        if (!$moduleManager->isEnabled('Budgets')) {
            return redirect()->route('settings.modules')->with('error', 'Budgets module is not enabled. Please enable it in Settings > Modules.');
        }

        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $response = $this->budgetClient->listBudgets($request->user(), ['per_page' => 100]);
        if (!$response->successful()) {
            return back()->withErrors(['error' => 'Failed to load budgets']);
        }

        $budgets = collect($response->json('data.data') ?? [])->map(fn ($budget) => $this->mapBudget($budget));

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgets,
        ]);
    }

    public function create()
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        return Inertia::render('Budgets/Create');
    }

    public function store(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'period' => 'required|in:daily,weekly,monthly,quarterly,annual,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['notes'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'period_type' => $validated['period'],
            'total_amount' => $validated['amount'],
            'currency_code' => $request->user()->organization?->currency ?? 'ZMW',
        ];

        $response = $this->budgetClient->createBudget($request->user(), $payload);
        if (!$response->successful()) {
            return back()->withErrors(['error' => 'Failed to create budget']);
        }

        return redirect()->route('budgets.index')->with('message', 'Budget created successfully');
    }

    public function edit($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $response = $this->budgetClient->getBudget($request->user(), $id);
        if (!$response->successful()) {
            return redirect()->route('budgets.index')->withErrors(['error' => 'Budget not found']);
        }

        return Inertia::render('Budgets/Edit', [
            'budget' => $this->mapBudget($response->json('data')),
        ]);
    }

    public function update(Request $request, $id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'period' => 'required|in:daily,weekly,monthly,quarterly,annual,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['notes'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'period_type' => $validated['period'],
            'total_amount' => $validated['amount'],
            'currency_code' => $request->user()->organization?->currency ?? 'ZMW',
        ];

        $response = $this->budgetClient->updateBudget($request->user(), $id, $payload);
        if (!$response->successful()) {
            return back()->withErrors(['error' => 'Failed to update budget']);
        }

        return redirect()->route('budgets.index')->with('message', 'Budget updated successfully');
    }

    public function destroy($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $response = $this->budgetClient->deleteBudget($request->user(), $id);
        if (!$response->successful()) {
            return back()->withErrors(['error' => 'Failed to delete budget']);
        }

        return redirect()->route('budgets.index')->with('message', 'Budget deleted successfully');
    }

    protected function mapBudget(array $budget): array
    {
        return [
            'id' => $budget['id'] ?? null,
            'name' => $budget['name'] ?? '',
            'category' => $budget['category'] ?? null,
            'amount' => $budget['total_amount'] ?? $budget['amount'] ?? 0,
            'spent' => $budget['spent_amount'] ?? $budget['spent'] ?? 0,
            'remaining' => $budget['remaining_amount'] ?? $budget['remaining'] ?? 0,
            'percentage_spent' => $budget['utilization_percentage'] ?? $budget['percentage_spent'] ?? 0,
            'period' => $budget['period_type'] ?? $budget['period'] ?? null,
            'start_date' => $budget['start_date'] ?? null,
            'end_date' => $budget['end_date'] ?? null,
            'notes' => $budget['description'] ?? ($budget['notes'] ?? null),
        ];
    }
}
