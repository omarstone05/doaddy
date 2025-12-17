<?php

namespace App\Services\Addy;

use App\Models\Organization;
use App\Models\AddyPrediction;
use App\Models\MoneyMovement;
use App\Models\Invoice;
use App\Models\BudgetLine;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AddyPredictiveEngine
{
    protected Organization $organization;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    /**
     * Generate all predictions
     */
    public function generatePredictions(): void
    {
        $this->predictCashFlow();
        $this->predictBudgetBurn();
        $this->predictSalesRevenue();
        $this->predictInventoryNeeds();
    }

    /**
     * Predict cash flow for next 30, 60, 90 days
     */
    protected function predictCashFlow(): void
    {
        $currentCash = \App\Models\MoneyAccount::where('organization_id', $this->organization->id)
            ->where('is_active', true)
            ->sum('current_balance');

        // Get historical data
        $monthlyExpenses = $this->getAverageMonthlyExpenses();
        $monthlyRevenue = $this->getAverageMonthlyRevenue();
        $monthlyNet = $monthlyRevenue - $monthlyExpenses;

        // Predict for 30, 60, 90 days
        $periods = [30, 60, 90];
        
        foreach ($periods as $days) {
            $months = $days / 30;
            $predictedCash = $currentCash + ($monthlyNet * $months);

            // Adjust for known upcoming events
            $predictedCash = $this->adjustForUpcomingEvents($predictedCash, $days);

            // Calculate confidence based on data consistency
            $confidence = $this->calculateCashFlowConfidence();

            AddyPrediction::create([
                'organization_id' => $this->organization->id,
                'type' => 'cash_flow',
                'category' => 'money',
                'prediction_date' => today(),
                'target_date' => today()->addDays($days),
                'predicted_value' => $predictedCash,
                'confidence' => $confidence,
                'factors' => [
                    'current_cash' => $currentCash,
                    'monthly_expenses' => $monthlyExpenses,
                    'monthly_revenue' => $monthlyRevenue,
                    'monthly_net' => $monthlyNet,
                ],
                'metadata' => [
                    'days_ahead' => $days,
                    'runway_days' => $monthlyNet < 0 ? (int)($currentCash / abs($monthlyNet * 30)) : null,
                ],
            ]);
        }
    }

    /**
     * Predict when budgets will hit limits
     */
    protected function predictBudgetBurn(): void
    {
        $budgets = BudgetLine::where('organization_id', $this->organization->id)
            ->get();

        foreach ($budgets as $budget) {
            $spent = MoneyMovement::where('organization_id', $this->organization->id)
                ->where('budget_line_id', $budget->id)
                ->where('flow_type', 'expense')
                ->where('status', 'approved')
                ->sum('amount');
            
            $remaining = $budget->amount - $spent;

            if ($remaining <= 0) {
                continue; // Already exceeded
            }

            // Calculate daily burn rate
            $periodStart = now()->startOfMonth(); // Assuming monthly budgets
            $daysElapsed = max(1, now()->diffInDays($periodStart));
            $dailyBurnRate = $spent / $daysElapsed;

            if ($dailyBurnRate <= 0) {
                continue;
            }

            // Predict when it will run out
            $daysUntilExhausted = (int)($remaining / $dailyBurnRate);
            $exhaustionDate = today()->addDays($daysUntilExhausted);

            AddyPrediction::create([
                'organization_id' => $this->organization->id,
                'type' => 'budget_burn',
                'category' => 'money',
                'prediction_date' => today(),
                'target_date' => $exhaustionDate,
                'predicted_value' => 0, // Budget will hit zero
                'confidence' => 0.75,
                'factors' => [
                    'budget_name' => $budget->name,
                    'budget_amount' => $budget->amount,
                    'spent' => $spent,
                    'remaining' => $remaining,
                    'daily_burn_rate' => $dailyBurnRate,
                ],
                'metadata' => [
                    'budget_id' => $budget->id,
                    'days_until_exhausted' => $daysUntilExhausted,
                ],
            ]);
        }
    }

    /**
     * Predict next month's sales revenue
     */
    protected function predictSalesRevenue(): void
    {
        // Get last 3 months of sales data
        $salesData = [];
        for ($i = 0; $i < 3; $i++) {
            $month = now()->subMonths($i);
            $sales = Invoice::where('organization_id', $this->organization->id)
                ->where('status', 'paid')
                ->whereBetween('created_at', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth()
                ])
                ->sum('total_amount');
            
            $salesData[] = $sales;
        }

        // Simple linear regression for trend
        $avgSales = array_sum($salesData) / count($salesData);
        $trend = count($salesData) >= 2 ? ($salesData[0] - $salesData[2]) / 2 : 0; // Recent vs older

        $predictedSales = $avgSales + $trend;

        // Adjust for seasonality (placeholder - would need more data)
        $seasonalFactor = 1.0;
        $predictedSales *= $seasonalFactor;

        AddyPrediction::create([
            'organization_id' => $this->organization->id,
            'type' => 'sales_revenue',
            'category' => 'sales',
            'prediction_date' => today(),
            'target_date' => today()->addMonth()->endOfMonth(),
            'predicted_value' => $predictedSales,
            'confidence' => 0.7,
            'factors' => [
                'avg_sales' => $avgSales,
                'trend' => $trend,
                'historical_data' => $salesData,
            ],
        ]);
    }

    /**
     * Predict inventory reorder needs
     */
    protected function predictInventoryNeeds(): void
    {
        // This would analyze stock movement patterns
        // and predict when items need reordering
        // Placeholder for now - can be enhanced later
    }

    /**
     * Get average monthly expenses
     */
    protected function getAverageMonthlyExpenses(): float
    {
        $months = 3;
        $total = 0;

        for ($i = 0; $i < $months; $i++) {
            $month = now()->subMonths($i);
            $total += MoneyMovement::where('organization_id', $this->organization->id)
                ->where('flow_type', 'expense')
                ->where('status', 'approved')
                ->whereBetween('transaction_date', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth()
                ])
                ->sum('amount');
        }

        return $total / max(1, $months);
    }

    /**
     * Get average monthly revenue
     */
    protected function getAverageMonthlyRevenue(): float
    {
        $months = 3;
        $total = 0;

        for ($i = 0; $i < $months; $i++) {
            $month = now()->subMonths($i);
            $total += MoneyMovement::where('organization_id', $this->organization->id)
                ->where('flow_type', 'income')
                ->where('status', 'approved')
                ->whereBetween('transaction_date', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth()
                ])
                ->sum('amount');
        }

        return $total / max(1, $months);
    }

    /**
     * Adjust prediction for known upcoming events
     */
    protected function adjustForUpcomingEvents(float $predicted, int $days): float
    {
        // Check for upcoming payroll
        $payroll = PayrollRun::where('organization_id', $this->organization->id)
            ->where('status', 'pending')
            ->where('payment_date', '<=', today()->addDays($days))
            ->sum('total_amount');

        // Check for pending invoices expected
        $expectedRevenue = Invoice::where('organization_id', $this->organization->id)
            ->where('status', 'sent')
            ->where('due_date', '<=', today()->addDays($days))
            ->sum('total_amount');

        return $predicted - $payroll + ($expectedRevenue * 0.7); // Assume 70% collection rate
    }

    /**
     * Calculate confidence in cash flow prediction
     */
    protected function calculateCashFlowConfidence(): float
    {
        // Based on data consistency, transaction volume, etc.
        // Placeholder: return medium confidence
        return 0.75;
    }
}

