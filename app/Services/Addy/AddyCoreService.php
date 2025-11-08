<?php

namespace App\Services\Addy;

use App\Models\AddyState;
use App\Models\AddyInsight;
use App\Models\Organization;
use App\Services\Addy\Agents\MoneyAgent;
use App\Services\Addy\Agents\SalesAgent;
use App\Services\Addy\Agents\PeopleAgent;
use App\Services\Addy\Agents\InventoryAgent;

class AddyCoreService
{
    protected Organization $organization;
    protected AddyState $state;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
        $this->state = $this->getOrCreateState();
    }

    protected function getOrCreateState(): AddyState
    {
        $state = AddyState::current($this->organization->id);

        if (!$state) {
            $state = AddyState::create([
                'organization_id' => $this->organization->id,
                'focus_area' => null,
                'urgency' => 0,
                'context' => 'Initializing Addy...',
                'mood' => 'neutral',
                'perception_data' => [],
                'priorities' => [],
                'last_thought_cycle' => now(),
            ]);
        }

        return $state;
    }

    public function runDecisionLoop(): AddyState
    {
        // Step 1: Perception - ALL AGENTS NOW
        $perceptionData = $this->perceive();

        // Step 2: Context
        $context = $this->analyzeContext($perceptionData);

        // Step 3: Decision
        $decision = $this->makeDecision($context);

        // Step 4: Action (includes cross-section insights)
        $this->generateInsights($decision, $perceptionData);

        // Step 5: Update state
        $this->state->update([
            'focus_area' => $decision['focus_area'],
            'urgency' => $decision['urgency'],
            'context' => $decision['context'],
            'mood' => $decision['mood'],
            'perception_data' => $perceptionData,
            'priorities' => $decision['priorities'],
            'last_thought_cycle' => now(),
        ]);

        return $this->state->fresh();
    }

    protected function perceive(): array
    {
        $data = [];

        // Money Agent
        $moneyAgent = new MoneyAgent($this->organization);
        $data['money'] = $moneyAgent->perceive();

        // Sales Agent (NEW)
        $salesAgent = new SalesAgent($this->organization);
        $data['sales'] = $salesAgent->perceive();

        // People Agent (NEW)
        $peopleAgent = new PeopleAgent($this->organization);
        $data['people'] = $peopleAgent->perceive();

        // Inventory Agent (NEW)
        $inventoryAgent = new InventoryAgent($this->organization);
        $data['inventory'] = $inventoryAgent->perceive();

        return $data;
    }

    protected function analyzeContext(array $perceptionData): array
    {
        $issues = [];
        $opportunities = [];
        $observations = [];

        // Money analysis
        if (isset($perceptionData['money'])) {
            $money = $perceptionData['money'];

            if (!empty($money['budget_health']['overrun'])) {
                $issues[] = [
                    'area' => 'money',
                    'type' => 'budget_overrun',
                    'severity' => 0.9,
                    'data' => $money['budget_health']['overrun'],
                ];
            }

            if (!empty($money['budget_health']['warning'])) {
                $issues[] = [
                    'area' => 'money',
                    'type' => 'budget_warning',
                    'severity' => 0.6,
                    'data' => $money['budget_health']['warning'],
                ];
            }

            if (isset($money['trends']) && $money['trends']['trend'] === 'increasing' && 
                $money['trends']['change_percentage'] > 20) {
                $issues[] = [
                    'area' => 'money',
                    'type' => 'spending_spike',
                    'severity' => 0.8,
                    'data' => $money['trends'],
                ];
            }
        }

        // Sales analysis (NEW)
        if (isset($perceptionData['sales'])) {
            $sales = $perceptionData['sales'];

            if ($sales['invoice_health']['overdue_count'] > 0) {
                $issues[] = [
                    'area' => 'sales',
                    'type' => 'overdue_invoices',
                    'severity' => 0.85,
                    'data' => $sales['invoice_health'],
                ];
            }

            if ($sales['sales_performance']['trend'] === 'decreasing' && 
                $sales['sales_performance']['change_percentage'] < -10) {
                $issues[] = [
                    'area' => 'sales',
                    'type' => 'sales_decline',
                    'severity' => 0.8,
                    'data' => $sales['sales_performance'],
                ];
            }

            if ($sales['sales_performance']['trend'] === 'increasing' && 
                $sales['sales_performance']['change_percentage'] > 20) {
                $opportunities[] = [
                    'area' => 'sales',
                    'type' => 'sales_growth',
                    'data' => $sales['sales_performance'],
                ];
            }
        }

        // People analysis (NEW)
        if (isset($perceptionData['people'])) {
            $people = $perceptionData['people'];

            if (isset($people['payroll_health']['days_until_payroll']) && 
                $people['payroll_health']['days_until_payroll'] <= 7) {
                $issues[] = [
                    'area' => 'people',
                    'type' => 'payroll_due',
                    'severity' => 0.85,
                    'data' => $people['payroll_health'],
                ];
            }

            if ($people['leave_patterns']['pending_requests'] > 0) {
                $issues[] = [
                    'area' => 'people',
                    'type' => 'pending_leave',
                    'severity' => 0.6,
                    'data' => $people['leave_patterns'],
                ];
            }
        }

        // Inventory analysis (NEW)
        if (isset($perceptionData['inventory'])) {
            $inventory = $perceptionData['inventory'];

            if ($inventory['stock_levels']['out_of_stock'] > 0) {
                $issues[] = [
                    'area' => 'inventory',
                    'type' => 'out_of_stock',
                    'severity' => 0.9,
                    'data' => $inventory,
                ];
            }

            if ($inventory['stock_levels']['low_stock'] > 0) {
                $issues[] = [
                    'area' => 'inventory',
                    'type' => 'low_stock',
                    'severity' => 0.75,
                    'data' => $inventory,
                ];
            }
        }

        return [
            'issues' => $issues,
            'opportunities' => $opportunities,
            'observations' => $observations,
        ];
    }

    protected function makeDecision(array $context): array
    {
        $focusArea = null;
        $urgency = 0;
        $contextText = '';
        $mood = 'neutral';
        $priorities = [];

        $issues = collect($context['issues'])->sortByDesc('severity');

        if ($issues->isNotEmpty()) {
            $topIssue = $issues->first();
            $focusArea = ucfirst($topIssue['area']);
            $urgency = $topIssue['severity'];

            $contextMessages = [
                'budget_overrun' => [
                    'context' => 'Budget overrun detected - immediate attention needed',
                    'mood' => 'concerned',
                    'priorities' => ['Review budget allocations', 'Identify non-essential expenses', 'Plan corrective actions'],
                ],
                'budget_warning' => [
                    'context' => 'Budget approaching limits - monitoring required',
                    'mood' => 'attentive',
                    'priorities' => ['Monitor remaining budget', 'Review upcoming expenses'],
                ],
                'spending_spike' => [
                    'context' => 'Unusual spending pattern detected',
                    'mood' => 'concerned',
                    'priorities' => ['Analyze spending patterns', 'Identify one-time vs recurring costs'],
                ],
                'overdue_invoices' => [
                    'context' => 'Overdue invoices affecting cash flow',
                    'mood' => 'concerned',
                    'priorities' => ['Send payment reminders', 'Review payment terms', 'Follow up with customers'],
                ],
                'sales_decline' => [
                    'context' => 'Sales performance declining - action needed',
                    'mood' => 'concerned',
                    'priorities' => ['Review customer engagement', 'Analyze market conditions', 'Consider promotional campaigns'],
                ],
                'payroll_due' => [
                    'context' => 'Payroll payment approaching',
                    'mood' => 'attentive',
                    'priorities' => ['Review payroll items', 'Ensure sufficient funds', 'Approve payroll run'],
                ],
                'out_of_stock' => [
                    'context' => 'Critical inventory shortage detected',
                    'mood' => 'concerned',
                    'priorities' => ['Place urgent restock orders', 'Update product availability', 'Notify customers'],
                ],
                'low_stock' => [
                    'context' => 'Inventory levels running low',
                    'mood' => 'attentive',
                    'priorities' => ['Review reorder levels', 'Place purchase orders', 'Check supplier availability'],
                ],
            ];

            $message = $contextMessages[$topIssue['type']] ?? [
                'context' => 'Issue detected requiring attention',
                'mood' => 'attentive',
                'priorities' => ['Review and take action'],
            ];

            $contextText = $message['context'];
            $mood = $message['mood'];
            $priorities = $message['priorities'];

        } else {
            // No critical issues
            $focusArea = 'Overview';
            $urgency = 0.3;
            $contextText = 'Business running smoothly';
            $mood = 'optimistic';
            $priorities = ['Continue monitoring'];
        }

        return [
            'focus_area' => $focusArea,
            'urgency' => $urgency,
            'context' => $contextText,
            'mood' => $mood,
            'priorities' => $priorities,
        ];
    }

    protected function generateInsights(array $decision, array $perceptionData): void
    {
        // Clear old insights
        AddyInsight::where('organization_id', $this->organization->id)
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        $allInsights = [];

        // Get insights from all agents
        $moneyAgent = new MoneyAgent($this->organization);
        $allInsights = array_merge($allInsights, $moneyAgent->analyze());

        $salesAgent = new SalesAgent($this->organization);
        $allInsights = array_merge($allInsights, $salesAgent->analyze());

        $peopleAgent = new PeopleAgent($this->organization);
        $allInsights = array_merge($allInsights, $peopleAgent->analyze());

        $inventoryAgent = new InventoryAgent($this->organization);
        $allInsights = array_merge($allInsights, $inventoryAgent->analyze());

        // Generate cross-section insights (NEW!)
        $crossInsights = $this->generateCrossSectionInsights($perceptionData);
        $allInsights = array_merge($allInsights, $crossInsights);

        // Create insight records
        foreach ($allInsights as $insightData) {
            $existing = AddyInsight::where('organization_id', $this->organization->id)
                ->where('category', $insightData['category'])
                ->where('title', $insightData['title'])
                ->where('status', 'active')
                ->first();

            if (!$existing) {
                AddyInsight::create([
                    'organization_id' => $this->organization->id,
                    'addy_state_id' => $this->state->id,
                    'type' => $insightData['type'],
                    'category' => $insightData['category'],
                    'title' => $insightData['title'],
                    'description' => $insightData['description'],
                    'priority' => $insightData['priority'],
                    'is_actionable' => $insightData['is_actionable'],
                    'suggested_actions' => $insightData['suggested_actions'],
                    'action_url' => $insightData['action_url'],
                    'status' => 'active',
                ]);
            }
        }
    }

    /**
     * CROSS-SECTION INSIGHTS - The magic of Phase 2!
     * Insights that connect data across multiple business areas
     */
    protected function generateCrossSectionInsights(array $perceptionData): array
    {
        $insights = [];

        // CROSS-INSIGHT 1: Low inventory + High sales = Potential stockout
        if (isset($perceptionData['inventory']) && isset($perceptionData['sales'])) {
            $lowStock = $perceptionData['inventory']['stock_levels']['low_stock'];
            $salesTrend = $perceptionData['sales']['sales_performance']['trend'] ?? 'stable';

            if ($lowStock > 0 && $salesTrend === 'increasing') {
                $insights[] = [
                    'type' => 'alert',
                    'category' => 'cross-section',
                    'title' => '⚠️ Sales Growth + Low Inventory Risk',
                    'description' => "You have {$lowStock} low-stock item(s) while sales are increasing. " .
                        "This could lead to stockouts and lost sales opportunities.",
                    'priority' => 0.88,
                    'is_actionable' => true,
                    'suggested_actions' => [
                        'Prioritize restocking popular items',
                        'Increase reorder quantities',
                        'Consider bulk purchasing discounts',
                        'Communicate delivery times to customers',
                    ],
                    'action_url' => '/stock',
                ];
            }
        }

        // CROSS-INSIGHT 2: Overdue invoices + Upcoming payroll = Cash flow squeeze
        if (isset($perceptionData['sales']) && isset($perceptionData['people'])) {
            $overdueAmount = $perceptionData['sales']['invoice_health']['overdue_amount'] ?? 0;
            $payrollDays = $perceptionData['people']['payroll_health']['days_until_payroll'] ?? null;
            $payrollAmount = $perceptionData['people']['payroll_health']['next_payroll_amount'] ?? 0;

            if ($overdueAmount > 0 && $payrollDays !== null && $payrollDays <= 10 && $payrollAmount > 0) {
                $insights[] = [
                    'type' => 'alert',
                    'category' => 'cross-section',
                    'title' => '💰 Cash Flow Alert: Payroll + Overdue Invoices',
                    'description' => "You have " . number_format($overdueAmount, 2) . 
                        " in overdue invoices while payroll of " . number_format($payrollAmount, 2) . 
                        " is due in {$payrollDays} days.",
                    'priority' => 0.92,
                    'is_actionable' => true,
                    'suggested_actions' => [
                        'Urgently follow up on overdue invoices',
                        'Consider offering early payment discounts',
                        'Review cash reserves',
                        'Arrange short-term financing if needed',
                    ],
                    'action_url' => '/invoices',
                ];
            }
        }

        // CROSS-INSIGHT 3: Sales decline + High expenses = Profit margin squeeze
        if (isset($perceptionData['sales']) && isset($perceptionData['money'])) {
            $salesTrend = $perceptionData['sales']['sales_performance']['trend'] ?? 'stable';
            $salesChange = $perceptionData['sales']['sales_performance']['change_percentage'] ?? 0;
            $spendingTrend = $perceptionData['money']['trends']['trend'] ?? 'stable';

            if ($salesTrend === 'decreasing' && $salesChange < -10 && $spendingTrend === 'increasing') {
                $insights[] = [
                    'type' => 'alert',
                    'category' => 'cross-section',
                    'title' => '📉 Profit Margin Squeeze',
                    'description' => "Sales are declining while expenses are rising - your profit margins are under pressure.",
                    'priority' => 0.9,
                    'is_actionable' => true,
                    'suggested_actions' => [
                        'Review and cut non-essential expenses',
                        'Implement sales recovery strategies',
                        'Analyze pricing strategy',
                        'Review operational efficiency',
                    ],
                    'action_url' => '/reports/profit-loss',
                ];
            }
        }

        // CROSS-INSIGHT 4: High leave volume + Sales goals = Capacity planning
        if (isset($perceptionData['people']) && isset($perceptionData['sales'])) {
            $upcomingLeave = $perceptionData['people']['leave_patterns']['upcoming_count'] ?? 0;
            $salesTrend = $perceptionData['sales']['sales_performance']['trend'] ?? 'stable';

            if ($upcomingLeave > 5 && $salesTrend === 'increasing') {
                $insights[] = [
                    'type' => 'suggestion',
                    'category' => 'cross-section',
                    'title' => '👥 Capacity Planning: Leave + High Sales',
                    'description' => "{$upcomingLeave} team members on leave while sales are growing. Plan for capacity constraints.",
                    'priority' => 0.7,
                    'is_actionable' => true,
                    'suggested_actions' => [
                        'Redistribute workload among available team',
                        'Consider temporary staff or overtime',
                        'Adjust delivery timelines',
                        'Communicate capacity to customers',
                    ],
                    'action_url' => '/leave/requests',
                ];
            }
        }

        // CROSS-INSIGHT 5: Budget overrun in inventory + Out of stock = Poor planning
        if (isset($perceptionData['money']) && isset($perceptionData['inventory'])) {
            $budgetOverruns = $perceptionData['money']['budget_health']['overrun'] ?? [];
            $outOfStock = $perceptionData['inventory']['stock_levels']['out_of_stock'] ?? 0;

            // Check if inventory budget is overrun
            $inventoryOverrun = collect($budgetOverruns)->first(function($budget) {
                return stripos($budget['name'], 'inventory') !== false || 
                       stripos($budget['name'], 'stock') !== false ||
                       stripos($budget['name'], 'purchase') !== false;
            });

            if ($inventoryOverrun && $outOfStock > 0) {
                $insights[] = [
                    'type' => 'suggestion',
                    'category' => 'cross-section',
                    'title' => '📊 Inventory Planning Inefficiency',
                    'description' => "Inventory budget is overspent yet you have {$outOfStock} out-of-stock item(s). " .
                        "This suggests inventory allocation issues.",
                    'priority' => 0.75,
                    'is_actionable' => true,
                    'suggested_actions' => [
                        'Review inventory purchasing strategy',
                        'Implement demand forecasting',
                        'Analyze which items are overstocked',
                        'Optimize reorder levels and quantities',
                    ],
                    'action_url' => '/stock',
                ];
            }
        }

        return $insights;
    }

    public function getState(): AddyState
    {
        return $this->state->fresh();
    }

    public function getActiveInsights()
    {
        return AddyInsight::active($this->organization->id)->get();
    }

    public function getCurrentThought(): array
    {
        $insights = $this->getActiveInsights();
        $topInsight = $insights->sortByDesc('priority')->first();

        return [
            'state' => [
                'focus_area' => $this->state->focus_area ?? 'Overview',
                'urgency' => (float) ($this->state->urgency ?? 0.2),
                'context' => $this->state->context ?? 'Initializing Addy...',
                'mood' => $this->state->mood ?? 'neutral',
                'priorities' => $this->state->priorities ?? [],
                'last_updated' => $this->state->last_thought_cycle?->diffForHumans() ?? null,
            ],
            'top_insight' => $topInsight ? [
                'id' => $topInsight->id,
                'type' => $topInsight->type,
                'title' => $topInsight->title,
                'description' => $topInsight->description,
                'priority' => (float) $topInsight->priority,
                'actions' => $topInsight->suggested_actions,
                'url' => $topInsight->action_url,
            ] : null,
            'insights_count' => $insights->count(),
        ];
    }
}
