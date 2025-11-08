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
            [
                'key' => 'revenue',
                'name' => 'Revenue',
                'component' => 'RevenueCard',
                'default_config' => ['period' => 'month'],
            ],
            [
                'key' => 'expenses',
                'name' => 'Expenses',
                'component' => 'ExpensesCard',
                'default_config' => ['period' => 'month'],
            ],
            [
                'key' => 'net_balance',
                'name' => 'Net Balance',
                'component' => 'NetBalanceCard',
                'default_config' => [],
            ],
            [
                'key' => 'accounts',
                'name' => 'Accounts',
                'component' => 'AccountsCard',
                'default_config' => [],
            ],
            [
                'key' => 'recent_movements',
                'name' => 'Recent Movements',
                'component' => 'RecentMovementsCard',
                'default_config' => ['limit' => 5],
            ],
            [
                'key' => 'budgets',
                'name' => 'Budgets',
                'component' => 'BudgetsCard',
                'default_config' => [],
            ],
            [
                'key' => 'quick_actions',
                'name' => 'Quick Actions',
                'component' => 'QuickActionsCard',
                'default_config' => [],
            ],
        ];

        foreach ($cards as $card) {
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
