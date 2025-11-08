<?php

namespace App\Services\Addy;

use App\Models\Organization;
use App\Services\Addy\Agents\MoneyAgent;
use App\Services\Addy\Agents\SalesAgent;
use App\Services\Addy\Agents\PeopleAgent;
use App\Services\Addy\Agents\InventoryAgent;
use App\Services\AI\AIService;

class AddyResponseGenerator
{
    protected Organization $organization;
    protected AddyCoreService $core;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
        $this->core = new AddyCoreService($organization);
    }

    /**
     * Generate response based on intent
     */
    public function generateResponse(array $intent, string $userMessage, array $chatHistory = []): array
    {
        switch ($intent['intent']) {
            case 'greeting':
                return $this->handleGreeting();
            
            case 'query_cash':
                return $this->handleCashQuery();
            
            case 'query_budget':
                return $this->handleBudgetQuery();
            
            case 'query_expenses':
                return $this->handleExpensesQuery();
            
            case 'query_invoices':
                return $this->handleInvoicesQuery($intent['type'] ?? 'all');
            
            case 'query_sales':
                return $this->handleSalesQuery();
            
            case 'query_team':
                return $this->handleTeamQuery();
            
            case 'query_payroll':
                return $this->handlePayrollQuery();
            
            case 'query_inventory':
                return $this->handleInventoryQuery();
            
            case 'query_focus':
                return $this->handleFocusQuery();
            
            case 'query_insights':
                return $this->handleInsightsQuery();
            
            case 'general':
            default:
                return $this->handleGeneralQuery($userMessage, $chatHistory);
        }
    }

    protected function handleGreeting(): array
    {
        $state = $this->core->getState();
        $insights = $this->core->getActiveInsights();
        
        $greeting = "Hi! I'm Addy, your business COO. ";
        
        if ($insights->count() > 0) {
            $greeting .= "I have {$insights->count()} insight(s) for you. ";
        }
        
        if ($state->focus_area) {
            $greeting .= "Currently focusing on: {$state->focus_area}.";
        }
        
        $greeting .= "\n\nWhat would you like to know?";
        
        return [
            'content' => $greeting,
            'quick_actions' => [
                ['label' => '💰 Cash Position', 'command' => 'What is my cash position?'],
                ['label' => '📊 Top Expenses', 'command' => 'Show me top expenses'],
                ['label' => '🎯 What to focus on', 'command' => 'What should I focus on today?'],
            ],
        ];
    }

    protected function handleCashQuery(): array
    {
        $agent = new MoneyAgent($this->organization);
        $data = $agent->perceive();
        
        $cash = number_format($data['cash_position'], 2);
        $topExpenses = $data['top_expenses'];
        
        $response = "Your current cash position is **\${$cash}** across all accounts.\n\n";
        
        if (!empty($topExpenses)) {
            $response .= "**Top 3 expenses this month:**\n";
            foreach ($topExpenses as $expense) {
                $amount = number_format($expense['amount'], 2);
                $response .= "• {$expense['category']}: \${$amount}\n";
            }
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '📊 View Accounts', 'url' => '/money/accounts'],
                ['label' => '💸 View Expenses', 'url' => '/money/movements'],
                ['label' => '📈 View Budgets', 'url' => '/money/budgets'],
            ],
        ];
    }

    protected function handleBudgetQuery(): array
    {
        $agent = new MoneyAgent($this->organization);
        $data = $agent->perceive();
        $budgetHealth = $data['budget_health'];
        
        $response = "**Budget Status:**\n\n";
        
        if (!empty($budgetHealth['overrun'])) {
            $response .= "🔴 **Over Budget:**\n";
            foreach ($budgetHealth['overrun'] as $budget) {
                $percentage = number_format($budget['percentage'], 0);
                $response .= "• {$budget['name']}: {$percentage}% spent\n";
            }
            $response .= "\n";
        }
        
        if (!empty($budgetHealth['warning'])) {
            $response .= "🟡 **Approaching Limit:**\n";
            foreach ($budgetHealth['warning'] as $budget) {
                $percentage = number_format($budget['percentage'], 0);
                $response .= "• {$budget['name']}: {$percentage}% spent\n";
            }
            $response .= "\n";
        }
        
        if (!empty($budgetHealth['healthy'])) {
            $healthyCount = count($budgetHealth['healthy']);
            $response .= "🟢 **Healthy:**\n";
            $response .= "• {$healthyCount} budget(s) in good standing\n";
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '📊 View All Budgets', 'url' => '/money/budgets'],
            ],
        ];
    }

    protected function handleExpensesQuery(): array
    {
        $agent = new MoneyAgent($this->organization);
        $data = $agent->perceive();
        
        $burn = number_format($data['monthly_burn'], 2);
        $trends = $data['trends'];
        $topExpenses = $data['top_expenses'];
        
        $response = "**Expense Overview:**\n\n";
        $response .= "💸 **Monthly burn:** \${$burn}\n\n";
        
        if ($trends['trend'] === 'increasing') {
            $response .= "📈 Spending is **up {$trends['change_percentage']}%** from last month.\n\n";
        } elseif ($trends['trend'] === 'decreasing') {
            $response .= "📉 Spending is **down " . abs($trends['change_percentage']) . "%** from last month.\n\n";
        } else {
            $response .= "➡️ Spending is **stable** compared to last month.\n\n";
        }
        
        if (!empty($topExpenses)) {
            $response .= "**Top categories:**\n";
            foreach ($topExpenses as $expense) {
                $amount = number_format($expense['amount'], 2);
                $response .= "• {$expense['category']}: \${$amount}\n";
            }
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '💸 View Transactions', 'url' => '/money/movements'],
                ['label' => '📊 View Report', 'url' => '/reports/expenses'],
            ],
        ];
    }

    protected function handleInvoicesQuery(string $type): array
    {
        $agent = new SalesAgent($this->organization);
        $data = $agent->perceive();
        $invoiceHealth = $data['invoice_health'];
        
        $response = "**Invoice Status:**\n\n";
        
        if ($type === 'overdue' || $type === 'all') {
            if ($invoiceHealth['overdue_count'] > 0) {
                $amount = number_format($invoiceHealth['overdue_amount'], 2);
                $response .= "🔴 **{$invoiceHealth['overdue_count']} overdue invoice(s)** totaling \${$amount}\n\n";
            }
        }
        
        if ($type === 'pending' || $type === 'all') {
            if ($invoiceHealth['pending_count'] > 0) {
                $amount = number_format($invoiceHealth['pending_amount'], 2);
                $response .= "🟡 **{$invoiceHealth['pending_count']} pending invoice(s)** totaling \${$amount}\n\n";
            }
        }
        
        if ($invoiceHealth['overdue_count'] === 0 && $invoiceHealth['pending_count'] === 0) {
            $response .= "✅ All invoices are paid! Great work.\n";
        }
        
        $quickActions = [
            ['label' => '📄 View All Invoices', 'url' => '/invoices'],
        ];
        
        if ($invoiceHealth['overdue_count'] > 0) {
            $quickActions[] = ['label' => '📧 Send Reminders', 'command' => 'Draft payment reminder emails'];
        }
        
        return [
            'content' => $response,
            'quick_actions' => $quickActions,
        ];
    }

    protected function handleSalesQuery(): array
    {
        $agent = new SalesAgent($this->organization);
        $data = $agent->perceive();
        
        $performance = $data['sales_performance'];
        $customers = $data['customer_stats'];
        
        $thisMonth = number_format($performance['current_month'], 2);
        $lastMonth = number_format($performance['last_month'], 2);
        
        $response = "**Sales Performance:**\n\n";
        $response .= "💰 **This month:** \${$thisMonth}\n";
        $response .= "📊 **Last month:** \${$lastMonth}\n\n";
        
        if ($performance['trend'] === 'increasing') {
            $response .= "📈 Sales are **up {$performance['change_percentage']}%**! Excellent work.\n\n";
        } elseif ($performance['trend'] === 'decreasing') {
            $response .= "📉 Sales are **down " . abs($performance['change_percentage']) . "%**. Time to review strategy.\n\n";
        } else {
            $response .= "➡️ Sales are **stable**.\n\n";
        }
        
        $response .= "**Customer stats:**\n";
        $response .= "• Total customers: {$customers['total']}\n";
        $response .= "• Active: {$customers['active']}\n";
        $response .= "• New this month: {$customers['new_this_month']}\n";
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '📊 Sales Report', 'url' => '/reports/sales'],
                ['label' => '👥 View Customers', 'url' => '/customers'],
            ],
        ];
    }

    protected function handleTeamQuery(): array
    {
        $agent = new PeopleAgent($this->organization);
        $data = $agent->perceive();
        
        $stats = $data['team_stats'];
        $leave = $data['leave_patterns'];
        
        $response = "**Team Overview:**\n\n";
        $response .= "👥 **Total team members:** {$stats['total']}\n";
        $response .= "✅ **Active:** {$stats['active']}\n";
        $response .= "🏖️ **On leave:** {$stats['on_leave']}\n\n";
        
        if ($stats['new_this_month'] > 0) {
            $response .= "🎉 Welcome to {$stats['new_this_month']} new team member(s) this month!\n\n";
        }
        
        if ($leave['pending_requests'] > 0) {
            $response .= "⏳ {$leave['pending_requests']} leave request(s) pending approval.\n";
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '👥 View Team', 'url' => '/team'],
                ['label' => '🏖️ Leave Requests', 'url' => '/leave/requests'],
            ],
        ];
    }

    protected function handlePayrollQuery(): array
    {
        $agent = new PeopleAgent($this->organization);
        $data = $agent->perceive();
        
        $payroll = $data['payroll_health'];
        
        $response = "**Payroll Status:**\n\n";
        
        if ($payroll['next_payroll_date']) {
            $amount = number_format($payroll['next_payroll_amount'], 2);
            $days = $payroll['days_until_payroll'];
            
            $response .= "💰 **Next payroll:** \${$amount}\n";
            $response .= "📅 **Due in:** {$days} day(s)\n\n";
            
            if ($days <= 7) {
                $response .= "⚠️ Payroll is due soon. Make sure funds are available.\n";
            }
        } else {
            $response .= "No upcoming payroll scheduled.\n";
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '💰 View Payroll', 'url' => '/payroll/runs'],
            ],
        ];
    }

    protected function handleInventoryQuery(): array
    {
        $agent = new InventoryAgent($this->organization);
        $data = $agent->perceive();
        
        $levels = $data['stock_levels'];
        $value = number_format($data['inventory_value'], 2);
        
        $response = "**Inventory Status:**\n\n";
        $response .= "📦 **Total products:** {$levels['total_products']}\n";
        $response .= "💵 **Total value:** \${$value}\n\n";
        
        $response .= "**Stock levels:**\n";
        $response .= "🟢 Healthy: {$levels['healthy']}\n";
        
        if ($levels['low_stock'] > 0) {
            $response .= "🟡 Low stock: {$levels['low_stock']}\n";
        }
        
        if ($levels['out_of_stock'] > 0) {
            $response .= "🔴 Out of stock: {$levels['out_of_stock']}\n";
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '📦 View Inventory', 'url' => '/stock'],
                ['label' => '🔄 Stock Movements', 'url' => '/stock/movements'],
            ],
        ];
    }

    protected function handleFocusQuery(): array
    {
        $state = $this->core->getState();
        $thought = $this->core->getCurrentThought();
        
        $response = "**Your Focus Today:**\n\n";
        $response .= "🎯 **Area:** {$state->focus_area}\n";
        $response .= "📝 **Context:** {$state->context}\n\n";
        
        if (!empty($state->priorities)) {
            $response .= "**Priorities:**\n";
            foreach ($state->priorities as $priority) {
                $response .= "• {$priority}\n";
            }
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '💡 View Insights', 'command' => 'Show me all insights'],
            ],
        ];
    }

    protected function handleInsightsQuery(): array
    {
        $insights = $this->core->getActiveInsights();
        
        if ($insights->isEmpty()) {
            return [
                'content' => "✨ All clear! No urgent insights right now. Your business is running smoothly.",
            ];
        }
        
        $response = "**Active Insights ({$insights->count()}):**\n\n";
        
        foreach ($insights->take(3) as $insight) {
            $emoji = match($insight->type) {
                'alert' => '🔴',
                'suggestion' => '💡',
                'observation' => '📊',
                'achievement' => '🎉',
                default => '•',
            };
            
            $response .= "{$emoji} **{$insight->title}**\n";
            $response .= "   {$insight->description}\n\n";
        }
        
        if ($insights->count() > 3) {
            $more = $insights->count() - 3;
            $response .= "_+ {$more} more insight(s)_\n";
        }
        
        return [
            'content' => $response,
        ];
    }

    protected function handleGeneralQuery(string $userMessage, array $chatHistory): array
    {
        // Use AI for general conversation
        $ai = new AIService();
        
        // Get current business context
        $state = $this->core->getState();
        $thought = $this->core->getCurrentThought();
        
        $systemMessage = "You are Addy, a helpful business COO assistant. ";
        $systemMessage .= "Current business focus: {$state->focus_area}. ";
        $systemMessage .= "Context: {$state->context}. ";
        
        if ($thought['top_insight']) {
            $systemMessage .= "Top insight: {$thought['top_insight']['title']}. ";
        }
        
        // Build message array with history
        $messages = [
            ['role' => 'system', 'content' => $systemMessage]
        ];
        
        // Add recent chat history for context
        foreach ($chatHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
        
        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];
        
        try {
            $response = $ai->chat($messages, 500);
            
            return [
                'content' => $response['content'],
            ];
        } catch (\Exception $e) {
            return [
                'content' => "I'm having trouble connecting right now. Please try again in a moment.",
            ];
        }
    }
}

