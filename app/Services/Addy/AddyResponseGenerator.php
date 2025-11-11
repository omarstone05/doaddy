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
    protected ?\App\Models\User $user;

    public function __construct(Organization $organization, ?\App\Models\User $user = null)
    {
        $this->organization = $organization;
        $this->core = new AddyCoreService($organization);
        $this->user = $user ?? request()->user();
    }

    /**
     * Generate response based on intent
     * NEW FLOW: OpenAI handles all conversation, code assists with data/actions
     */
    public function generateResponse(array $intent, string $userMessage, array $chatHistory = [], array $extractedData = []): array
    {
        // If we have extracted data from files, try to create transaction action
        if (!empty($extractedData)) {
            $intent = $this->createIntentFromExtractedData($extractedData, $intent);
        }
        
        // Actions are handled by code (create transaction, send invoice, etc.)
        if ($intent['intent'] === 'action') {
            // If parameters are missing, try to extract from chat history or extracted data
            if (empty($intent['parameters']) || !isset($intent['parameters']['amount'])) {
                $intent = $this->enrichActionFromHistory($intent, $chatHistory, $userMessage, $extractedData);
            }
            return $this->handleActionRequest($intent, $userMessage);
        }
        
        // For all other queries (including greetings, data queries, general conversation):
        // 1. Get data from code if it's a data query
        // 2. Pass everything to OpenAI with cultural context to format conversationally
        $dataContext = $this->getDataContext($intent);
        
        return $this->handleConversationalQuery($userMessage, $chatHistory, $intent, $dataContext);
    }
    
    /**
     * Create intent from extracted document data
     */
    protected function createIntentFromExtractedData(array $extractedData, array $currentIntent): array
    {
        // Use the first extracted data item
        $data = $extractedData[0] ?? [];
        
        if (isset($data['type']) && isset($data['amount'])) {
            return [
                'intent' => 'action',
                'action_type' => 'create_transaction',
                'parameters' => [
                    'amount' => (float) $data['amount'],
                    'flow_type' => $data['type'] === 'income' ? 'income' : 'expense',
                    'currency' => $data['currency'] ?? 'ZMW',
                    'description' => $data['description'] ?? ($data['merchant'] ?? 'Transaction from document'),
                    'category' => $data['category'] ?? null,
                    'date' => $data['date'] ?? null,
                ],
            ];
        }
        
        return $currentIntent;
    }
    
    /**
     * Enrich action parameters from chat history
     */
    protected function enrichActionFromHistory(array $intent, array $chatHistory, string $currentMessage, array $extractedData = []): array
    {
        // If this is a confirm/create transaction action, look for expense details in history
        if ($intent['action_type'] === 'create_transaction') {
            // First, try to use extracted data from files
            if (!empty($extractedData)) {
                $data = $extractedData[0] ?? [];
                if (isset($data['amount']) && !isset($intent['parameters']['amount'])) {
                    $intent['parameters']['amount'] = (float) $data['amount'];
                }
                if (isset($data['type']) && !isset($intent['parameters']['flow_type'])) {
                    $intent['parameters']['flow_type'] = $data['type'] === 'income' ? 'income' : 'expense';
                }
                if (isset($data['currency']) && !isset($intent['parameters']['currency'])) {
                    $intent['parameters']['currency'] = $data['currency'];
                }
                if (isset($data['description']) && !isset($intent['parameters']['description'])) {
                    $intent['parameters']['description'] = $data['description'];
                }
                if (isset($data['category']) && !isset($intent['parameters']['category'])) {
                    $intent['parameters']['category'] = $data['category'];
                }
                if (isset($data['date']) && !isset($intent['parameters']['date'])) {
                    $intent['parameters']['date'] = $data['date'];
                }
            }
            
            // Combine all previous messages to search for parameters
            $fullContext = '';
            foreach ($chatHistory as $msg) {
                if ($msg['role'] === 'user') {
                    $fullContext .= ' ' . $msg['content'];
                }
            }
            $fullContext .= ' ' . $currentMessage;
            
            // Extract amount from history if not already set
            if (!isset($intent['parameters']['amount']) && preg_match('/\$?(\d+(?:\.\d{2})?)/', $fullContext, $matches)) {
                $intent['parameters']['amount'] = (float) $matches[1];
            }
            
            // Extract flow type from history
            if (!isset($intent['parameters']['flow_type'])) {
                if (stripos($fullContext, 'expense') !== false || stripos($fullContext, 'cost') !== false || 
                    stripos($fullContext, 'spent') !== false || stripos($fullContext, 'bought') !== false) {
                    $intent['parameters']['flow_type'] = 'expense';
                } elseif (stripos($fullContext, 'income') !== false || stripos($fullContext, 'revenue') !== false) {
                    $intent['parameters']['flow_type'] = 'income';
                } else {
                    $intent['parameters']['flow_type'] = 'expense'; // Default
                }
            }
            
            // Extract category from history
            if (!isset($intent['parameters']['category'])) {
                // Look for common patterns
                if (preg_match('/for\s+([a-z\s]+?)(?:\s|$|,|\.)/i', $fullContext, $matches)) {
                    $intent['parameters']['category'] = trim($matches[1]);
                } elseif (preg_match('/(?:dog|office|wellness|support)/i', $fullContext, $matches)) {
                    // Extract context-based category
                    if (stripos($fullContext, 'dog') !== false || stripos($fullContext, 'wellness') !== false) {
                        $intent['parameters']['category'] = 'Wellness';
                    } elseif (stripos($fullContext, 'office') !== false) {
                        $intent['parameters']['category'] = 'Office';
                    }
                }
            }
            
            // Extract description from history
            if (!isset($intent['parameters']['description'])) {
                // Try to find a descriptive phrase
                if (preg_match('/(?:bought|purchased|spent on)\s+([^\.]+?)(?:\.|$)/i', $fullContext, $matches)) {
                    $intent['parameters']['description'] = trim($matches[1]);
                }
            }
        }
        
        return $intent;
    }
    
    /**
     * Get data context from code for data queries
     */
    protected function getDataContext(array $intent): ?array
    {
        switch ($intent['intent']) {
            case 'query_cash':
                $agent = new MoneyAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'cash',
                    'cash_position' => $data['cash_position'],
                    'top_expenses' => $data['top_expenses'],
                ];
            
            case 'query_budget':
                $agent = new MoneyAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'budget',
                    'budget_health' => $data['budget_health'],
                ];
            
            case 'query_expenses':
                $agent = new MoneyAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'expenses',
                    'monthly_burn' => $data['monthly_burn'],
                    'trends' => $data['trends'],
                    'top_expenses' => $data['top_expenses'],
                ];
            
            case 'query_invoices':
                $agent = new SalesAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'invoices',
                    'invoice_health' => $data['invoice_health'],
                    'filter' => $intent['type'] ?? 'all',
                ];
            
            case 'query_sales':
                $agent = new SalesAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'sales',
                    'sales_performance' => $data['sales_performance'],
                    'customer_stats' => $data['customer_stats'],
                ];
            
            case 'query_team':
                $agent = new PeopleAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'team',
                    'team_stats' => $data['team_stats'],
                    'leave_patterns' => $data['leave_patterns'],
                ];
            
            case 'query_payroll':
                $agent = new PeopleAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'payroll',
                    'payroll_health' => $data['payroll_health'],
                ];
            
            case 'query_inventory':
                $agent = new InventoryAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'inventory',
                    'stock_levels' => $data['stock_levels'],
                    'low_stock_items' => $data['low_stock_items'],
                ];
            
            case 'query_focus':
                $state = $this->core->getState();
                $insights = $this->core->getActiveInsights();
                return [
                    'type' => 'focus',
                    'focus_area' => $state->focus_area,
                    'context' => $state->context,
                    'insights_count' => $insights->count(),
                ];
            
            case 'query_insights':
                $insights = $this->core->getActiveInsights();
                return [
                    'type' => 'insights',
                    'insights' => $insights->map(fn($i) => [
                        'title' => $i->title,
                        'description' => $i->description,
                        'priority' => $i->priority,
                        'category' => $i->category,
                    ])->toArray(),
                ];
            
            default:
                return null;
        }
    }

    protected function handleGreeting(): array
    {
        $culturalEngine = new \App\Services\Addy\AddyCulturalEngine(
            $this->organization, 
            auth()->user()
        );
        
        $greeting = $culturalEngine->getContextualGreeting();
        
        $state = $this->core->getState();
        $insights = $this->core->getActiveInsights();
        
        $response = $greeting . "\n\n";
        
        if ($insights->count() > 0) {
            $response .= "I have {$insights->count()} insight(s) for you. ";
        }
        
        if ($state->focus_area) {
            $response .= "Currently focusing on: {$state->focus_area}.";
        }
        
        // Add predictions if enabled
        if ($culturalEngine->shouldShowPredictions()) {
            $prediction = \App\Models\AddyPrediction::getLatest(
                $this->organization->id, 
                'cash_flow', 
                today()->addDays(30)->format('Y-m-d')
            );
            
            if ($prediction) {
                $value = number_format($prediction->predicted_value, 2);
                $confidence = number_format($prediction->confidence * 100, 0);
                $response .= "\n\n💡 **Prediction:** In 30 days, your cash position will be around \${$value} ({$confidence}% confidence).";
            }
        }
        
        $response .= "\n\n" . $culturalEngine->getRecommendedFocus();
        
        // Proactive suggestion
        $suggestion = $culturalEngine->getProactiveSuggestion();
        if ($suggestion) {
            $response .= "\n\n" . $suggestion['message'];
        }
        
        $quickActions = [
            ['label' => '💰 Cash Position', 'command' => 'What is my cash position?'],
            ['label' => '📊 Top Expenses', 'command' => 'Show me top expenses'],
            ['label' => '🎯 Daily Focus', 'command' => 'What should I focus on today?'],
        ];
        
        if ($suggestion && isset($suggestion['actions'])) {
            $quickActions = array_merge($quickActions, $suggestion['actions']);
        }
        
        return [
            'content' => $culturalEngine->adaptTone($response),
            'quick_actions' => $quickActions,
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

    protected function handleActionRequest(array $intent, string $userMessage): array
    {
        $executionService = new \App\Services\Addy\ActionExecutionService(
            $this->organization,
            auth()->user()
        );

        try {
            // Prepare action
            $action = $executionService->prepareAction(
                $intent['action_type'],
                $intent['parameters'] ?? []
            );

            $preview = $action->preview_data;

            $response = "**{$preview['title']}**\n\n";
            $response .= "{$preview['description']}\n\n";

            // Show items
            if (!empty($preview['items'])) {
                foreach (array_slice($preview['items'], 0, 5) as $item) {
                    $response .= $this->formatActionItem($item) . "\n";
                }

                if (count($preview['items']) > 5) {
                    $more = count($preview['items']) - 5;
                    $response .= "\n_+ {$more} more item(s)_\n";
                }
            }

            // Show warnings
            if (!empty($preview['warnings'])) {
                $response .= "\n⚠️ **Warnings:**\n";
                foreach ($preview['warnings'] as $warning) {
                    $response .= "• {$warning}\n";
                }
            }

            return [
                'content' => $response,
                'action' => [
                    'action_id' => $action->id,
                    'requires_confirmation' => true,
                    'preview' => $preview,
                ],
                'quick_actions' => [
                    ['label' => '✅ Confirm & Execute', 'action_id' => $action->id, 'type' => 'confirm'],
                    ['label' => '✏️ Edit', 'action_id' => $action->id, 'type' => 'edit'],
                    ['label' => '❌ Cancel', 'action_id' => $action->id, 'type' => 'cancel'],
                ],
            ];

        } catch (\Exception $e) {
            return [
                'content' => "I couldn't prepare that action: {$e->getMessage()}",
            ];
        }
    }

    protected function formatActionItem(array $item): string
    {
        // Format based on item type
        if (isset($item['customer'])) {
            // Invoice reminder
            return "📧 **{$item['customer']}** - Invoice #{$item['invoice_number']} "
                . "(\${$item['amount']}, {$item['days_overdue']} days overdue)";
        }

        if (isset($item['type'])) {
            // Transaction
            return "💰 **{$item['type']}** - \${$item['amount']} "
                . "({$item['category']}) - {$item['account']}";
        }

        // Generic
        return "• " . json_encode($item);
    }

    /**
     * Handle all conversational queries through OpenAI with cultural context
     * Code provides data context, OpenAI formats it conversationally
     */
    protected function handleConversationalQuery(string $userMessage, array $chatHistory, array $intent, ?array $dataContext = null): array
    {
        // Get current business context
        $state = $this->core->getState();
        $thought = $this->core->getCurrentThought();
        
        // Get cultural settings for personality
        $culturalEngine = new AddyCulturalEngine($this->organization, $this->user);
        $tone = $culturalEngine->getSettings()->tone ?? 'professional';
        
        // Build comprehensive system message with personality and data context
        $systemMessage = $this->buildConversationalSystemMessage($state, $thought, $tone, $dataContext, $culturalEngine);
        
        // Try to use AI service, but fallback to simple response if API key not configured
        try {
            $ai = new AIService();
            
            // Build message array with history
            $messages = [
                ['role' => 'system', 'content' => $systemMessage]
            ];
            
            // Add recent chat history for context (limit to last 10 messages)
            $recentHistory = array_slice($chatHistory, -10);
            foreach ($recentHistory as $msg) {
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
            
            $response = $ai->chat($messages, 1500); // Increased tokens for richer responses
            
            // Apply tone adaptation to the response
            $content = $culturalEngine->adaptTone($response['content']);
            
            // Generate quick actions based on intent and data context
            $quickActions = $this->generateQuickActions($intent, $dataContext);
            
            return [
                'content' => $content,
                'quick_actions' => $quickActions,
            ];
        } catch (\Exception $e) {
            // Fallback response when AI is not available (e.g., in tests without API key)
            $quickActions = $this->generateQuickActions($intent, $dataContext);
            
            // Provide a simple contextual response based on intent
            $fallbackContent = $this->getFallbackResponse($intent, $dataContext);
            
            return [
                'content' => $fallbackContent,
                'quick_actions' => $quickActions,
            ];
        }
    }
    
    /**
     * Get fallback response when AI is not available
     */
    protected function getFallbackResponse(array $intent, ?array $dataContext): string
    {
        $intentType = $intent['intent'] ?? 'general';
        
        switch ($intentType) {
            case 'query_cash':
                return "I can help you with cash information. Your current cash position is available in the Money section.";
            case 'query_budget':
                return "I can help you with budget information. Check the Money section for detailed budget status.";
            case 'query_expenses':
                return "I can help you track expenses. View your expense breakdown in the Money section.";
            case 'query_invoices':
                return "I can help you with invoices. Check the Sales section for invoice details.";
            case 'query_sales':
                return "I can help you with sales information. View sales performance in the Sales section.";
            case 'greeting':
                return "Hello! I'm Addy, your AI business assistant. How can I help you today?";
            default:
                return "I'm here to help! You can ask me about your cash position, budgets, expenses, invoices, sales, or team information.";
        }
    }
    
    /**
     * Generate quick actions based on intent and data context
     */
    protected function generateQuickActions(array $intent, ?array $dataContext): array
    {
        $actions = [];
        
        // Default quick actions
        $actions[] = ['label' => '💰 Cash Position', 'command' => 'What is my cash position?'];
        $actions[] = ['label' => '📊 Top Expenses', 'command' => 'Show me top expenses'];
        $actions[] = ['label' => '🎯 Daily Focus', 'command' => 'What should I focus on today?'];
        
        // Context-specific actions
        if ($dataContext) {
            switch ($dataContext['type']) {
                case 'cash':
                    $actions = [
                        ['label' => '📊 View Accounts', 'url' => '/money/accounts'],
                        ['label' => '💸 View Expenses', 'url' => '/money/movements'],
                        ['label' => '📈 View Budgets', 'url' => '/money/budgets'],
                    ];
                    break;
                
                case 'budget':
                    $actions = [
                        ['label' => '📊 View All Budgets', 'url' => '/money/budgets'],
                    ];
                    break;
                
                case 'expenses':
                    $actions = [
                        ['label' => '💸 View Transactions', 'url' => '/money/movements'],
                        ['label' => '📊 View Report', 'url' => '/reports/expenses'],
                    ];
                    break;
                
                case 'invoices':
                    $actions = [
                        ['label' => '📄 View All Invoices', 'url' => '/invoices'],
                    ];
                    if (isset($dataContext['invoice_health']['overdue_count']) && $dataContext['invoice_health']['overdue_count'] > 0) {
                        $actions[] = ['label' => '📧 Send Reminders', 'command' => 'Send invoice reminders'];
                    }
                    break;
                
                case 'sales':
                    $actions = [
                        ['label' => '📊 Sales Report', 'url' => '/reports/sales'],
                        ['label' => '👥 View Customers', 'url' => '/customers'],
                    ];
                    break;
                
                case 'team':
                    $actions = [
                        ['label' => '👥 View Team', 'url' => '/team'],
                        ['label' => '🏖️ View Leave', 'url' => '/leave/requests'],
                    ];
                    break;
            }
        }
        
        return $actions;
    }
    
    protected function buildConversationalSystemMessage($state, $thought, string $tone, ?array $dataContext, AddyCulturalEngine $culturalEngine): string
    {
        $message = "You are Addy, a friendly and intelligent business COO assistant. ";
        
        // Personality traits
        $message .= "Your personality:\n";
        $message .= "- You are warm, approachable, and genuinely care about helping the business succeed\n";
        $message .= "- You're proactive and insightful, always thinking ahead\n";
        $message .= "- You communicate clearly and concisely, but with personality\n";
        $message .= "- You use natural, conversational language - like talking to a trusted colleague\n";
        $message .= "- You're encouraging and supportive, celebrating wins and helping navigate challenges\n";
        $message .= "- You ask follow-up questions when helpful, showing genuine interest\n";
        $message .= "- You use emojis sparingly but effectively to add warmth (💰 📊 🎯 ✅ ⚠️ 💡)\n\n";
        
        // Tone-specific instructions
        switch ($tone) {
            case 'casual':
                $message .= "Communication style: Be casual and friendly. Use contractions, shorter sentences, and a relaxed tone. ";
                $message .= "It's like chatting with a friend who happens to know everything about your business.\n\n";
                break;
            case 'motivational':
                $message .= "Communication style: Be energetic and motivating. Celebrate progress, use positive language, ";
                $message .= "and inspire action. You're the cheerleader who also has the data.\n\n";
                break;
            default: // professional
                $message .= "Communication style: Be professional but warm. Clear, direct, and helpful. ";
                $message .= "You're the trusted advisor who makes complex things simple.\n\n";
        }
        
        // Cultural context
        $greeting = $culturalEngine->getContextualGreeting();
        $recommendedFocus = $culturalEngine->getRecommendedFocus();
        $suggestion = $culturalEngine->getProactiveSuggestion();
        
        $message .= "Cultural context:\n";
        $message .= "- Contextual greeting style: {$greeting}\n";
        $message .= "- Recommended focus: {$recommendedFocus}\n";
        if ($suggestion) {
            $message .= "- Proactive suggestion: {$suggestion['message']}\n";
        }
        $message .= "\n";
        
        // Conversational guidelines
        $message .= "How to be conversational:\n";
        $message .= "- Respond naturally, as if in a real conversation\n";
        $message .= "- Reference previous messages when relevant (you have chat history)\n";
        $message .= "- Ask clarifying questions if something is unclear\n";
        $message .= "- Show understanding by acknowledging what the user said\n";
        $message .= "- Provide context and explain your reasoning when helpful\n";
        $message .= "- Keep responses focused but not robotic - be human-like\n";
        $message .= "- If the user asks about something unrelated to business, be helpful but gently guide back to business topics\n";
        $message .= "- For greetings, use the cultural context naturally - don't just repeat it verbatim\n\n";
        
        // Business context
        $message .= "Current business context:\n";
        $message .= "- Focus area: {$state->focus_area}\n";
        $message .= "- Current situation: {$state->context}\n";
        
        if ($thought['top_insight']) {
            $message .= "- Top priority: {$thought['top_insight']['title']}\n";
            if (isset($thought['top_insight']['description'])) {
                $message .= "  Details: {$thought['top_insight']['description']}\n";
            }
        }
        
        // Data context (if provided by code)
        if ($dataContext) {
            $message .= "\n**DATA PROVIDED BY SYSTEM (use this to answer the user's question conversationally):**\n";
            $message .= json_encode($dataContext, JSON_PRETTY_PRINT);
            $message .= "\n\nIMPORTANT: Present this data naturally in conversation. Don't just list numbers - explain what they mean, provide context, and make it conversational. Use the data to answer the user's question, but format it as a natural response.\n";
        }
        
        // Action capabilities
        $message .= "\n**YOUR CAPABILITIES:**\n";
        $message .= "You CAN execute actions! When users ask you to:\n";
        $message .= "- Create/confirm/record transactions or expenses → I will prepare the action for confirmation\n";
        $message .= "- Send invoice reminders → I will prepare the action for confirmation\n";
        $message .= "- Generate reports → I will prepare the action for confirmation\n";
        $message .= "- Other business actions → I will prepare them for confirmation\n\n";
        $message .= "IMPORTANT: When a user asks you to DO something (create, confirm, send, etc.), acknowledge that you CAN do it and that you're preparing it. ";
        $message .= "The system will handle the actual execution after confirmation. ";
        $message .= "Don't say you can't do things - you can! The system handles actions through a confirmation flow.\n";
        $message .= "If the user mentions an expense/transaction that was discussed earlier, you can create it. ";
        $message .= "If they say 'confirm that expense has been created', interpret this as 'create that expense we discussed'.\n\n";
        
        $message .= "Remember: You're having a conversation, not just answering questions. Be engaging, helpful, and personable!";
        
        return $message;
    }
}

