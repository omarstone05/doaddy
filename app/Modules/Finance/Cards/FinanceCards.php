<?php

namespace App\Modules\Finance\Cards;

use App\Services\Dashboard\CardRegistry;

/**
 * Finance Module Card Definitions
 * 
 * These are the core financial cards available in Addy
 */
class FinanceCards
{
    public static function register(): void
    {
        // Register the Finance module
        CardRegistry::registerModule('finance', [
            'name' => 'Finance',
            'description' => 'Financial tracking and reporting',
            'icon' => 'DollarSign',
            'color' => '#00635D', // Addy teal
        ]);

        // Register all cards
        self::registerRevenueCard();
        self::registerExpensesCard();
        self::registerProfitCard();
        self::registerCashFlowCard();
        self::registerRevenueChartCard();
        self::registerExpenseBreakdownCard();
        self::registerRecentTransactionsCard();
        self::registerMonthlyGoalCard();
    }

    protected static function registerRevenueCard(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue',
            'name' => 'Monthly Revenue',
            'description' => 'Total income for the current month',
            'component' => 'RevenueCard',
            'category' => 'metric',
            'size' => 'small',
            'icon' => 'TrendingUp',
            'color' => '#7DCD85',
            'priority' => 10,
            'suitable_for' => ['retail', 'consulting', 'agriculture', 'general'],
            'tags' => 'revenue income money sales',
            'data_endpoint' => '/api/dashboard/revenue',
            'refresh_interval' => 300,
        ]);
    }

    protected static function registerExpensesCard(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.expenses',
            'name' => 'Monthly Expenses',
            'description' => 'Total spending for the current month',
            'component' => 'ExpensesCard',
            'category' => 'metric',
            'size' => 'small',
            'icon' => 'TrendingDown',
            'color' => '#EF4444',
            'priority' => 9,
            'suitable_for' => ['retail', 'consulting', 'agriculture', 'general'],
            'tags' => 'expenses costs spending',
            'data_endpoint' => '/api/dashboard/expenses',
            'refresh_interval' => 300,
        ]);
    }

    protected static function registerProfitCard(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.profit',
            'name' => 'Net Profit',
            'description' => 'Revenue minus expenses',
            'component' => 'ProfitCard',
            'category' => 'metric',
            'size' => 'small',
            'icon' => 'DollarSign',
            'color' => '#00635D',
            'priority' => 10,
            'suitable_for' => ['retail', 'consulting', 'agriculture', 'general'],
            'tags' => 'profit margin earnings',
            'data_endpoint' => '/api/dashboard/profit',
            'refresh_interval' => 300,
        ]);
    }

    protected static function registerCashFlowCard(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.cash_flow',
            'name' => 'Cash Flow',
            'description' => 'Money in vs money out',
            'component' => 'CashFlowCard',
            'category' => 'metric',
            'size' => 'small',
            'icon' => 'ArrowLeftRight',
            'color' => '#00635D',
            'priority' => 7,
            'suitable_for' => ['retail', 'consulting', 'agriculture', 'general'],
            'tags' => 'cash flow liquidity',
            'data_endpoint' => '/api/dashboard/cash-flow',
            'refresh_interval' => 300,
        ]);
    }

    protected static function registerRevenueChartCard(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.revenue_chart',
            'name' => 'Revenue Over Time',
            'description' => 'Visual trend of your income',
            'component' => 'RevenueChartCard',
            'category' => 'chart',
            'size' => 'large',
            'icon' => 'LineChart',
            'color' => '#7DCD85',
            'priority' => 10,
            'suitable_for' => ['retail', 'consulting', 'agriculture', 'general'],
            'tags' => 'chart graph revenue trend',
            'data_endpoint' => '/api/dashboard/revenue-chart',
            'refresh_interval' => 600,
        ]);
    }

    protected static function registerExpenseBreakdownCard(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.expense_breakdown',
            'name' => 'Expense Breakdown',
            'description' => 'Where your money goes',
            'component' => 'ExpenseBreakdownCard',
            'category' => 'chart',
            'size' => 'medium',
            'icon' => 'PieChart',
            'color' => '#00635D',
            'priority' => 6,
            'suitable_for' => ['retail', 'consulting', 'agriculture', 'general'],
            'tags' => 'expenses categories breakdown pie chart',
            'data_endpoint' => '/api/dashboard/expense-breakdown',
            'refresh_interval' => 600,
        ]);
    }

    protected static function registerRecentTransactionsCard(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.recent_transactions',
            'name' => 'Recent Transactions',
            'description' => 'Latest financial activity',
            'component' => 'RecentTransactionsCard',
            'category' => 'list',
            'size' => 'wide',
            'icon' => 'List',
            'color' => '#00635D',
            'priority' => 8,
            'suitable_for' => ['retail', 'consulting', 'agriculture', 'general'],
            'tags' => 'transactions activity history',
            'data_endpoint' => '/api/dashboard/recent-transactions',
            'refresh_interval' => 120,
        ]);
    }

    protected static function registerMonthlyGoalCard(): void
    {
        CardRegistry::register('finance', [
            'id' => 'finance.monthly_goal',
            'name' => 'Monthly Goal',
            'description' => 'Progress towards your revenue target',
            'component' => 'MonthlyGoalCard',
            'category' => 'progress',
            'size' => 'medium',
            'icon' => 'Target',
            'color' => '#7DCD85',
            'priority' => 8,
            'suitable_for' => ['retail', 'consulting', 'agriculture', 'general'],
            'tags' => 'goal target progress',
            'data_endpoint' => '/api/dashboard/monthly-goal',
            'refresh_interval' => 300,
        ]);
    }
}

