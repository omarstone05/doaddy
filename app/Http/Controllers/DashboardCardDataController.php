<?php

namespace App\Http\Controllers;

use App\Models\MoneyMovement;
use App\Services\Dashboard\CardRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Modules\Consulting\Models\Project;
use App\Modules\Consulting\Models\Task;

class DashboardCardDataController extends Controller
{
    /**
     * Get data for a specific dashboard card
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

        // Cache key includes organization and card ID
        $cacheKey = "dashboard.card_data.{$organizationId}.{$cardId}";
        
        // Get refresh interval from card config (default 5 minutes)
        $cacheTTL = ($card['refresh_interval'] ?? 300) / 60; // Convert seconds to minutes
        
        // Try to get from cache first
        $data = Cache::remember($cacheKey, now()->addMinutes($cacheTTL), function () use ($cardId, $organizationId) {
            return $this->fetchCardData($organizationId, $cardId);
        });

        return response()->json($data);
    }

    /**
     * Get card data directly (for preloading)
     */
    public function getCardDataDirect(string $organizationId, string $cardId): array
    {
        $card = CardRegistry::getCard($cardId);
        
        if (!$card) {
            return ['error' => 'Card not found'];
        }

        // Cache key includes organization and card ID
        $cacheKey = "dashboard.card_data.{$organizationId}.{$cardId}";
        
        // Get refresh interval from card config (default 5 minutes)
        $cacheTTL = ($card['refresh_interval'] ?? 300) / 60; // Convert seconds to minutes
        
        // Try to get from cache first
        return Cache::remember($cacheKey, now()->addMinutes($cacheTTL), function () use ($cardId, $organizationId) {
            return $this->fetchCardData($organizationId, $cardId);
        });
    }

    /**
     * Fetch actual card data from database
     */
    protected function fetchCardData(string $organizationId, string $cardId): array
    {
        return match($cardId) {
            'finance.revenue' => $this->getRevenueData($organizationId),
            'finance.expenses' => $this->getExpensesData($organizationId),
            'finance.profit' => $this->getProfitData($organizationId),
            'finance.cash_flow' => $this->getCashFlowData($organizationId),
            'finance.revenue_chart' => $this->getRevenueChartData($organizationId),
            'finance.expense_breakdown' => $this->getExpenseBreakdownData($organizationId),
            'finance.recent_transactions' => $this->getRecentTransactionsData($organizationId),
            'finance.monthly_goal' => $this->getMonthlyGoalData($organizationId),
            'pm.active_projects' => $this->getActiveProjectsData($organizationId),
            'consulting.active_projects' => $this->getConsultingActiveProjectsData($organizationId),
            'consulting.project_health' => $this->getConsultingProjectHealthData($organizationId),
            'consulting.task_completion' => $this->getConsultingTaskCompletionData($organizationId),
            'consulting.upcoming_deadlines' => $this->getConsultingUpcomingDeadlinesData($organizationId),
            'consulting.project_progress' => $this->getConsultingProjectProgressData($organizationId),
            default => ['message' => 'No data available'],
        };
    }

    protected function getRevenueData(string $organizationId): array
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
            'amount' => (float) $current,
            'change' => round($change, 1),
            'comparison' => (float) $previous,
        ];
    }

    protected function getExpensesData(string $organizationId): array
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
            'amount' => (float) $current,
            'change' => round($change, 1),
            'comparison' => (float) $previous,
        ];
    }

    protected function getProfitData(string $organizationId): array
    {
        $revenue = $this->getRevenueData($organizationId);
        $expenses = $this->getExpensesData($organizationId);
        
        $profit = $revenue['amount'] - $expenses['amount'];
        $revenueChange = $revenue['change'];
        $expenseChange = $expenses['change'];

        return [
            'amount' => (float) $profit,
            'revenue' => $revenue['amount'],
            'expenses' => $expenses['amount'],
            'change' => ($revenueChange + abs($expenseChange)) / 2,
        ];
    }

    protected function getCashFlowData(string $organizationId): array
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
            'income' => (float) $income,
            'outgoing' => (float) $outgoing,
            'net' => (float) ($income - $outgoing),
        ];
    }

    protected function getRevenueChartData(string $organizationId): array
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

    protected function getExpenseBreakdownData(string $organizationId): array
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

    protected function getRecentTransactionsData(string $organizationId): array
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

    protected function getMonthlyGoalData(string $organizationId): array
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
            'current' => (float) $current,
            'goal' => (float) $goal,
            'percentage' => round($percentage, 1),
            'remaining' => (float) $remaining,
        ];
    }

    protected function getActiveProjectsData(string $organizationId): array
    {
        try {
            $count = \App\Models\Project::where('organization_id', $organizationId)
                ->where('status', 'in_progress')
                ->count();
            return ['count' => $count];
        } catch (\Exception $e) {
            \Log::warning('Active projects query failed', ['error' => $e->getMessage()]);
            return ['count' => 0];
        }
    }

    protected function getConsultingActiveProjectsData(string $organizationId): array
    {
        try {
            $projects = \App\Modules\Consulting\Models\Project::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->count();
            return ['count' => $projects];
        } catch (\Exception $e) {
            \Log::warning('Consulting active projects query failed', ['error' => $e->getMessage()]);
            return ['count' => 0];
        }
    }

    protected function getConsultingProjectHealthData(string $organizationId): array
    {
        try {
            $projects = \App\Modules\Consulting\Models\Project::where('organization_id', $organizationId)
                ->select('health_status', DB::raw('count(*) as count'))
                ->groupBy('health_status')
                ->get()
                ->map(function($item) {
                    return [
                        'status' => $item->health_status,
                        'count' => (int) $item->count,
                    ];
                });

            return ['health' => $projects];
        } catch (\Exception $e) {
            \Log::warning('Consulting project health query failed', ['error' => $e->getMessage()]);
            return ['health' => []];
        }
    }

    protected function getConsultingTaskCompletionData(string $organizationId): array
    {
        try {
            $tasks = \App\Modules\Consulting\Models\Task::whereHas('project', function($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function($item) {
                return [
                    'status' => $item->status,
                    'count' => (int) $item->count,
                ];
            });

            $total = $tasks->sum('count');
            $completed = $tasks->where('status', 'done')->sum('count');
            $completionRate = $total > 0 ? ($completed / $total) * 100 : 0;

            return [
                'tasks' => $tasks,
                'total' => $total,
                'completed' => $completed,
                'completion_rate' => round($completionRate, 1),
            ];
        } catch (\Exception $e) {
            \Log::warning('Consulting task completion query failed', ['error' => $e->getMessage()]);
            return ['tasks' => [], 'total' => 0, 'completed' => 0, 'completion_rate' => 0];
        }
    }

    protected function getConsultingUpcomingDeadlinesData(string $organizationId): array
    {
        try {
            $now = Carbon::now();
            $nextWeek = $now->copy()->addWeek();

            // Get tasks with upcoming deadlines
            $tasks = \App\Modules\Consulting\Models\Task::whereHas('project', function($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $now)
            ->where('due_date', '<=', $nextWeek)
            ->where('status', '!=', 'done')
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_date' => $task->due_date->toDateString(),
                    'project_name' => $task->project->name,
                    'priority' => $task->priority,
                ];
            });

            // Get projects with upcoming end dates
            $projects = \App\Modules\Consulting\Models\Project::where('organization_id', $organizationId)
                ->whereNotNull('end_date')
                ->where('end_date', '>=', $now)
                ->where('end_date', '<=', $nextWeek)
                ->where('status', '!=', 'complete')
                ->orderBy('end_date')
                ->limit(5)
                ->get()
                ->map(function($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'end_date' => $project->end_date->toDateString(),
                    ];
                });

            return [
                'tasks' => $tasks,
                'projects' => $projects,
            ];
        } catch (\Exception $e) {
            \Log::warning('Consulting upcoming deadlines query failed', ['error' => $e->getMessage()]);
            return ['tasks' => [], 'projects' => []];
        }
    }

    protected function getConsultingProjectProgressData(string $organizationId): array
    {
        try {
            $projects = \App\Modules\Consulting\Models\Project::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->select('id', 'name', 'progress_percentage', 'budget_total', 'budget_remaining')
                ->orderBy('progress_percentage', 'desc')
                ->limit(10)
                ->get()
                ->map(function($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'progress' => (float) $project->progress_percentage,
                        'budget_total' => (float) $project->budget_total,
                        'budget_remaining' => (float) $project->budget_remaining,
                    ];
                });

            $avgProgress = $projects->avg('progress') ?? 0;

            return [
                'projects' => $projects,
                'average_progress' => round($avgProgress, 1),
                'total_projects' => $projects->count(),
            ];
        } catch (\Exception $e) {
            \Log::warning('Consulting project progress query failed', ['error' => $e->getMessage()]);
            return ['projects' => [], 'average_progress' => 0, 'total_projects' => 0];
        }
    }
}

