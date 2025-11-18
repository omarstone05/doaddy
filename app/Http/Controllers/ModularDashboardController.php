<?php

namespace App\Http\Controllers;

use App\Models\MoneyMovement;
use App\Services\Dashboard\CardRegistry;
use App\Services\Dashboard\DashboardLayoutManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ModularDashboardController extends Controller
{
    protected DashboardLayoutManager $layoutManager;

    public function __construct(DashboardLayoutManager $layoutManager)
    {
        $this->layoutManager = $layoutManager;
    }

    /**
     * Show the modular dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !$user->current_organization_id) {
            return redirect()->route('login');
        }

        $layout = $this->layoutManager->getUserLayout($user);
        $availableCards = $this->layoutManager->getAvailableCards($user);

        return Inertia::render('Dashboard/ModularDashboard', [
            'layout' => $layout,
            'availableCards' => array_values($availableCards),
        ]);
    }

    /**
     * Get user's layout
     */
    public function getLayout(Request $request)
    {
        $user = Auth::user();
        $layout = $this->layoutManager->getUserLayout($user);
        
        return response()->json($layout);
    }

    /**
     * Get card data
     */
    public function getCardData(Request $request, string $cardId)
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') 
            ?? ($user->attributes['organization_id'] ?? null)
            ?? $user->organizations()->first()?->id;
        
        if (!$organizationId) {
            return response()->json(['error' => 'No organization selected'], 400);
        }

        $card = CardRegistry::getCard($cardId);
        
        if (!$card) {
            return response()->json(['error' => 'Card not found'], 404);
        }

        $data = $this->fetchCardData($user, $organizationId, $card);
        
        return response()->json($data);
    }

    /**
     * Get available cards
     */
    public function getAvailableCards(Request $request)
    {
        $user = Auth::user();
        $availableCards = $this->layoutManager->getAvailableCards($user);
        
        return response()->json(array_values($availableCards));
    }

    /**
     * Add card to dashboard
     */
    public function addCard(Request $request)
    {
        $request->validate([
            'card_id' => 'required|string',
            'row_id' => 'nullable|string',
            'position' => 'nullable|integer',
        ]);

        $user = Auth::user();
        
        try {
            $layout = $this->layoutManager->addCard(
                $user,
                $request->card_id,
                $request->row_id,
                $request->position
            );
            
            return response()->json($layout);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove card from dashboard
     */
    public function removeCard(Request $request)
    {
        $request->validate([
            'card_id' => 'required|string',
        ]);

        $user = Auth::user();
        
        try {
            $layout = $this->layoutManager->removeCard($user, $request->card_id);
            return response()->json($layout);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Move card
     */
    public function moveCard(Request $request)
    {
        $request->validate([
            'card_id' => 'required|string',
            'target_row_id' => 'required|string',
            'target_position' => 'required|integer',
        ]);

        $user = Auth::user();
        
        try {
            $layout = $this->layoutManager->moveCard(
                $user,
                $request->card_id,
                $request->target_row_id,
                $request->target_position
            );
            return response()->json($layout);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Pin/unpin card
     */
    public function pinCard(Request $request)
    {
        $request->validate([
            'card_id' => 'required|string',
        ]);

        $user = Auth::user();
        
        try {
            $layout = $this->layoutManager->togglePin($user, $request->card_id);
            return response()->json($layout);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Reset layout to default
     */
    public function resetLayout(Request $request)
    {
        $user = Auth::user();
        
        try {
            $layout = $this->layoutManager->resetToDefault($user);
            return response()->json($layout);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Fetch data for a specific card
     */
    protected function fetchCardData($user, $organizationId, array $card): array
    {
        return match($card['id']) {
            'finance.revenue' => $this->getRevenueData($organizationId),
            'finance.expenses' => $this->getExpensesData($organizationId),
            'finance.profit' => $this->getProfitData($organizationId),
            'finance.cash_flow' => $this->getCashFlowData($organizationId),
            'finance.revenue_chart' => $this->getRevenueChartData($organizationId),
            'finance.expense_breakdown' => $this->getExpenseBreakdownData($organizationId),
            'finance.recent_transactions' => $this->getRecentTransactionsData($organizationId),
            'finance.monthly_goal' => $this->getMonthlyGoalData($organizationId),
            'pm.active_projects' => $this->getActiveProjectsData($organizationId),
            default => ['message' => 'No data available'],
        };
    }

    protected function getRevenueData($organizationId): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $current = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $previous = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'amount' => $current,
            'change' => round($change, 1),
            'comparison' => $previous,
        ];
    }

    protected function getExpensesData($organizationId): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $current = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $previous = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'amount' => $current,
            'change' => round($change, 1),
            'comparison' => $previous,
        ];
    }

    protected function getProfitData($organizationId): array
    {
        $revenue = $this->getRevenueData($organizationId);
        $expenses = $this->getExpensesData($organizationId);
        
        $profit = $revenue['amount'] - $expenses['amount'];
        $revenueChange = $revenue['change'];
        $expenseChange = $expenses['change'];

        return [
            'amount' => $profit,
            'revenue' => $revenue['amount'],
            'expenses' => $expenses['amount'],
            'change' => ($revenueChange + abs($expenseChange)) / 2,
        ];
    }

    protected function getCashFlowData($organizationId): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $income = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $outgoing = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return [
            'income' => $income,
            'outgoing' => $outgoing,
            'net' => $income - $outgoing,
        ];
    }

    protected function getRevenueChartData($organizationId): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(30);
        
        $data = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->where('transaction_date', '>=', $startDate)
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'revenue' => (float) $item->revenue,
                ];
            });

        return ['data' => $data];
    }

    protected function getExpenseBreakdownData($organizationId): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $breakdown = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->category ?: 'Uncategorized',
                    'value' => (float) $item->total,
                ];
            });

        return ['breakdown' => $breakdown];
    }

    protected function getRecentTransactionsData($organizationId): array
    {
        $transactions = MoneyMovement::where('organization_id', $organizationId)
            ->where('status', 'approved')
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($movement) {
                return [
                    'id' => $movement->id,
                    'amount' => (float) $movement->amount,
                    'flow_type' => $movement->flow_type,
                    'description' => $movement->description,
                    'category' => $movement->category,
                    'date' => $movement->transaction_date->toDateString(),
                ];
            });

        return ['transactions' => $transactions];
    }

    protected function getMonthlyGoalData($organizationId): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $current = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Default goal or get from organization settings
        $goal = 100000; // TODO: Get from organization settings
        $percentage = $goal > 0 ? ($current / $goal) * 100 : 0;
        $remaining = max(0, $goal - $current);

        return [
            'current' => $current,
            'goal' => $goal,
            'percentage' => round($percentage, 1),
            'remaining' => $remaining,
        ];
    }

    protected function getActiveProjectsData($organizationId): array
    {
        // TODO: Implement when Project model exists
        return ['count' => 0];
    }
}

