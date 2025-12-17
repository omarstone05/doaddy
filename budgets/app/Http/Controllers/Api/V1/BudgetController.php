<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $budgets = Budget::query()
            ->where('organization_id', $request->user()->organization_id ?? null)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $budgets,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'period_type' => 'required|in:daily,weekly,monthly,quarterly,annual,custom',
            'total_amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
        ]);

        $budget = Budget::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
            'owner_id' => $request->user()->id,
            'budget_number' => 'BDG-' . now()->format('Y') . '-' . Budget::count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $budget,
        ], 201);
    }

    public function show(Budget $budget): JsonResponse
    {
        $this->authorizeOrg($budget, request()->user());

        return response()->json([
            'success' => true,
            'data' => $budget->load(['items', 'transactions', 'insights', 'alerts']),
        ]);
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeOrg($budget, $request->user());

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'period_type' => 'sometimes|required|in:daily,weekly,monthly,quarterly,annual,custom',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'currency_code' => 'sometimes|required|string|size:3',
            'allow_overspend' => 'sometimes|boolean',
            'require_approval' => 'sometimes|boolean',
            'alert_threshold' => 'sometimes|integer|min:1|max:100',
        ]);

        $budget->update($validated);

        return response()->json([
            'success' => true,
            'data' => $budget->fresh(),
        ]);
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeOrg($budget, $request->user());

        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget deleted',
        ]);
    }

    protected function authorizeOrg(Budget $budget, $user): void
    {
        if ($budget->organization_id !== ($user->organization_id ?? null)) {
            abort(403, 'Unauthorized');
        }
    }
}
