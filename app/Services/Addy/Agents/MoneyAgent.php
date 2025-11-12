<?php

namespace App\Services\Addy\Agents;

use App\Models\Organization;
use App\Models\MoneyMovement;
use App\Models\BudgetLine;
use App\Models\MoneyAccount;
use App\Traits\Cacheable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MoneyAgent
{
    use Cacheable;

    protected Organization $organization;
    protected int $cacheTtl = 300; // 5 minutes

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    protected function getOrganizationId(): int|string
    {
        return $this->organization->id;
    }

    public function perceive(): array
    {
        return $this->remember('perception', function () {
            return $this->doPerceive();
        });
    }

    protected function doPerceive(): array
    {
        return [
            'cash_position' => $this->getCashPosition(),
            'budget_health' => $this->getBudgetHealth(),
            'top_expenses' => $this->getTopExpenses(),
            'monthly_burn' => $this->getMonthlyBurn(),
            'trends' => $this->getTrends(),
            'latest_transactions' => $this->getLatestTransactions(),
        ];
    }

    protected function getCashPosition(): float
    {
        return MoneyAccount::where('organization_id', $this->organization->id)
            ->where('is_active', true)
            ->sum('current_balance');
    }

    protected function getBudgetHealth(): array
    {
        try {
            $budgets = BudgetLine::where('organization_id', $this->organization->id)
                ->get();
        } catch (\Exception $e) {
            // Handle case where organization_id column doesn't exist
            \Log::warning('Budget query failed - organization_id column may not exist', [
                'error' => $e->getMessage(),
                'organization_id' => $this->organization->id,
            ]);
            return [
                'overrun' => [],
                'warning' => [],
                'healthy' => [],
                'total_budgets' => 0,
            ];
        }

        $overrun = [];
        $healthy = [];
        $warning = [];

        foreach ($budgets as $budget) {
            $spent = $this->getSpentForBudget($budget);
            $percentage = $budget->amount > 0 ? ($spent / $budget->amount) * 100 : 0;

            if ($percentage >= 100) {
                $overrun[] = [
                    'name' => $budget->name,
                    'spent' => $spent,
                    'limit' => $budget->amount,
                    'percentage' => $percentage,
                ];
            } elseif ($percentage >= 80) {
                $warning[] = [
                    'name' => $budget->name,
                    'spent' => $spent,
                    'limit' => $budget->amount,
                    'percentage' => $percentage,
                ];
            } else {
                $healthy[] = [
                    'name' => $budget->name,
                    'spent' => $spent,
                    'limit' => $budget->amount,
                    'percentage' => $percentage,
                ];
            }
        }

        return [
            'overrun' => $overrun,
            'warning' => $warning,
            'healthy' => $healthy,
            'total_budgets' => count($budgets),
        ];
    }

    protected function getTopExpenses(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return MoneyMovement::where('organization_id', $this->organization->id)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(fn($item) => [
                'category' => $item->category ?? 'Uncategorized',
                'amount' => (float) $item->total,
            ])
            ->toArray();
    }

    protected function getMonthlyBurn(): float
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return (float) MoneyMovement::where('organization_id', $this->organization->id)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->where('transaction_date', '>=', $startOfMonth)
            ->sum('amount');
    }

    protected function getTrends(): array
    {
        $thisMonth = $this->getMonthlyBurn();
        $lastMonth = $this->getLastMonthBurn();
        $change = $lastMonth > 0 
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100 
            : 0;

        return [
            'current_month' => $thisMonth,
            'last_month' => $lastMonth,
            'change_percentage' => round($change, 2),
            'trend' => $change > 10 ? 'increasing' : ($change < -10 ? 'decreasing' : 'stable'),
        ];
    }

    protected function getLastMonthBurn(): float
    {
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        return (float) MoneyMovement::where('organization_id', $this->organization->id)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
    }

    protected function getSpentForBudget(BudgetLine $budget): float
    {
        $dates = $this->getBudgetPeriodDates($budget);

        return (float) MoneyMovement::where('organization_id', $this->organization->id)
            ->where('category', $budget->category)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dates['start'], $dates['end']])
            ->sum('amount');
    }

    protected function getBudgetPeriodDates(BudgetLine $budget): array
    {
        $period = $budget->period ?? 'monthly';
        
        return match($period) {
            'monthly' => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],
            'quarterly' => [
                'start' => Carbon::now()->startOfQuarter(),
                'end' => Carbon::now()->endOfQuarter(),
            ],
            'yearly' => [
                'start' => Carbon::now()->startOfYear(),
                'end' => Carbon::now()->endOfYear(),
            ],
            default => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],
        };
    }

    public function analyze(): array
    {
        $perception = $this->perceive();
        $insights = [];

        // Top expenses insight (always show for MVP)
        if (!empty($perception['top_expenses'])) {
            $insights[] = [
                'type' => 'observation',
                'category' => 'money',
                'title' => 'Top 3 Expenses This Month',
                'description' => $this->formatTopExpensesDescription($perception['top_expenses']),
                'priority' => 0.7,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Review expense patterns',
                    'Check if expenses align with budget',
                    'Identify optimization opportunities',
                ],
                'action_url' => '/money/movements',
            ];
        }

        // Budget overrun alerts
        if (!empty($perception['budget_health']['overrun'])) {
            foreach ($perception['budget_health']['overrun'] as $budget) {
                $insights[] = [
                    'type' => 'alert',
                    'category' => 'money',
                    'title' => "Budget Overrun: {$budget['name']}",
                    'description' => "You've spent " . number_format($budget['percentage'], 0) . "% of your {$budget['name']} budget.",
                    'priority' => 0.9,
                    'is_actionable' => true,
                    'suggested_actions' => [
                        'Review budget allocation',
                        'Postpone non-critical expenses',
                        'Adjust budget limits if needed',
                    ],
                    'action_url' => '/money/budgets',
                ];
            }
        }

        // Budget warnings
        if (!empty($perception['budget_health']['warning'])) {
            foreach ($perception['budget_health']['warning'] as $budget) {
                $insights[] = [
                    'type' => 'suggestion',
                    'category' => 'money',
                    'title' => "Budget Warning: {$budget['name']}",
                    'description' => "You're at " . number_format($budget['percentage'], 0) . "% of your {$budget['name']} budget.",
                    'priority' => 0.6,
                    'is_actionable' => true,
                    'suggested_actions' => [
                        'Monitor remaining expenses',
                        'Plan for the rest of the period',
                    ],
                    'action_url' => '/money/budgets',
                ];
            }
        }

        // Spending trend alert
        if ($perception['trends']['trend'] === 'increasing' && $perception['trends']['change_percentage'] > 20) {
            $insights[] = [
                'type' => 'alert',
                'category' => 'money',
                'title' => 'Spending Spike Detected',
                'description' => "Your expenses are up " . number_format($perception['trends']['change_percentage'], 1) . "% compared to last month.",
                'priority' => 0.8,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Review recent transactions',
                    'Identify unusual expenses',
                    'Check for one-time vs recurring costs',
                ],
                'action_url' => '/money/movements',
            ];
        }

        return $insights;
    }

    protected function formatTopExpensesDescription(array $expenses): string
    {
        $lines = [];
        foreach ($expenses as $expense) {
            $lines[] = "• {$expense['category']}: " . number_format($expense['amount'], 2);
        }
        return implode("\n", $lines);
    }

    /**
     * Get latest transactions for the organization
     */
    public function getLatestTransactions(int $limit = 10): array
    {
        return MoneyMovement::where('organization_id', $this->organization->id)
            ->where('status', 'approved')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'type' => $movement->flow_type,
                    'amount' => (float) $movement->amount,
                    'category' => $movement->category ?? 'Uncategorized',
                    'description' => $movement->description ?? '',
                    'date' => $movement->transaction_date->format('Y-m-d'),
                    'formatted_date' => $movement->transaction_date->format('M d, Y'),
                ];
            })
            ->toArray();
    }
}

