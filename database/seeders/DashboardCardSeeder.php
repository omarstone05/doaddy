<?php

namespace Database\Seeders;

use App\Models\DashboardCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DashboardCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            // Green Metric Cards (4)
            [
                'key' => 'total_revenue',
                'name' => 'Total Revenue',
                'component' => 'GreenMetricCard',
                'default_config' => ['type' => 'revenue'],
            ],
            [
                'key' => 'total_orders',
                'name' => 'Total Orders',
                'component' => 'GreenMetricCard',
                'default_config' => ['type' => 'orders'],
            ],
            [
                'key' => 'expenses_today',
                'name' => 'Expenses Today',
                'component' => 'GreenMetricCard',
                'default_config' => ['type' => 'expenses'],
            ],
            [
                'key' => 'net_balance',
                'name' => 'Net Balance',
                'component' => 'GreenMetricCard',
                'default_config' => ['type' => 'balance'],
            ],
            
            // Chart Cards (5)
            [
                'key' => 'revenue_chart',
                'name' => 'Revenue Trend',
                'component' => 'ChartCard',
                'default_config' => ['type' => 'revenue'],
            ],
            [
                'key' => 'cash_flow',
                'name' => 'Cash Flow',
                'component' => 'CashFlowCard',
                'default_config' => [],
            ],
            [
                'key' => 'revenue_by_category',
                'name' => 'Revenue by Category',
                'component' => 'RevenueByCategoryCard',
                'default_config' => [],
            ],
            [
                'key' => 'expense_breakdown',
                'name' => 'Expense Breakdown',
                'component' => 'ExpenseBreakdownCard',
                'default_config' => [],
            ],
            [
                'key' => 'customer_growth',
                'name' => 'Customer Growth',
                'component' => 'CustomerGrowthCard',
                'default_config' => [],
            ],
            
            // Information Cards (4)
            [
                'key' => 'top_products',
                'name' => 'Top Products',
                'component' => 'TopProductsCard',
                'default_config' => ['limit' => 5],
            ],
            [
                'key' => 'top_customers',
                'name' => 'Top Customers',
                'component' => 'TopCustomersCard',
                'default_config' => ['limit' => 5],
            ],
            [
                'key' => 'pending_invoices',
                'name' => 'Pending Invoices',
                'component' => 'PendingInvoicesCard',
                'default_config' => ['limit' => 5],
            ],
            [
                'key' => 'low_stock',
                'name' => 'Low Stock Alerts',
                'component' => 'LowStockCard',
                'default_config' => ['limit' => 5],
            ],
            
            // Status Cards (3)
            [
                'key' => 'profit_margin',
                'name' => 'Profit Margin',
                'component' => 'ProfitMarginCard',
                'default_config' => [],
            ],
            [
                'key' => 'budget_status',
                'name' => 'Budget Status',
                'component' => 'BudgetStatusCard',
                'default_config' => [],
            ],
            [
                'key' => 'recent_activity',
                'name' => 'Recent Activity',
                'component' => 'RecentActivityCard',
                'default_config' => ['limit' => 5],
            ],
            
            // Team/Project Cards (2)
            [
                'key' => 'team_performance',
                'name' => 'Team Performance',
                'component' => 'TeamPerformanceCard',
                'default_config' => [],
            ],
            [
                'key' => 'project_status',
                'name' => 'Project Status',
                'component' => 'ProjectStatusCard',
                'default_config' => [],
            ],
            
            // Quick Actions (separate - will be handled differently)
            [
                'key' => 'quick_actions',
                'name' => 'Quick Actions',
                'component' => 'QuickActionsCard',
                'default_config' => [],
            ],
        ];

        foreach ($cards as $card) {
            $existing = DashboardCard::where('key', $card['key'])->first();
            
            if ($existing) {
                $existing->update([
                    'name' => $card['name'],
                    'component' => $card['component'],
                    'default_config' => $card['default_config'],
                    'is_active' => true,
                ]);
            } else {
                DashboardCard::create([
                    'id' => (string) Str::uuid(),
                    'key' => $card['key'],
                    'name' => $card['name'],
                    'component' => $card['component'],
                    'default_config' => $card['default_config'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
