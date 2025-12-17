<?php

namespace App\NeuroCore\Adapters;

use App\NeuroCore\Contracts\DataProviderInterface;
use App\Models\Organization;
use App\Models\User;
use App\Services\Addy\Agents\MoneyAgent;
use App\Services\Addy\Agents\SalesAgent;
use App\Services\Addy\Agents\PeopleAgent;
use App\Services\Addy\Agents\InventoryAgent;
use App\Services\Addy\AddyCoreService;

/**
 * AddyDataProvider - Provides Addy-specific data to NeuroCore
 * This adapter pulls data from Addy's existing services and models
 */
class AddyDataProvider implements DataProviderInterface
{
    private Organization $organization;
    private ?User $user;

    public function __construct(Organization $organization, ?User $user = null)
    {
        $this->organization = $organization;
        $this->user = $user;
    }

    /**
     * Get the system identifier
     */
    public function getSystemId(): string
    {
        return 'addy';
    }

    /**
     * Get user's data summary from Addy
     */
    public function getUserDataSummary(string $userId): array
    {
        $summary = [
            'organization_name' => $this->organization->name,
            'organization_id' => $this->organization->id,
            'currency' => $this->organization->currency ?? 'ZMW',
        ];

        // Get quick stats from agents
        try {
            $moneyAgent = new MoneyAgent($this->organization);
            $moneyData = $moneyAgent->perceive();
            $summary['cash_position'] = $moneyData['cash_position'] ?? 0;
            $summary['monthly_burn'] = $moneyData['monthly_burn'] ?? 0;
        } catch (\Exception $e) {
            // Ignore failures
        }

        try {
            $salesAgent = new SalesAgent($this->organization);
            $salesData = $salesAgent->perceive();
            $summary['sales_this_month'] = $salesData['sales_performance']['current_month'] ?? 0;
            $summary['overdue_invoices'] = $salesData['invoice_health']['overdue_count'] ?? 0;
        } catch (\Exception $e) {
            // Ignore failures
        }

        try {
            $peopleAgent = new PeopleAgent($this->organization);
            $peopleData = $peopleAgent->perceive();
            $summary['team_size'] = $peopleData['team_stats']['total'] ?? 0;
        } catch (\Exception $e) {
            // Ignore failures
        }

        return $summary;
    }

    /**
     * Get relevant context for a query
     */
    public function getRelevantContext(string $userId, string $query, array $entities = []): array
    {
        $context = [];
        $lowerQuery = strtolower($query);

        // Determine which data is relevant based on query
        $topics = $this->detectTopics($lowerQuery);

        if (in_array('money', $topics) || in_array('cash', $topics) || in_array('finance', $topics)) {
            try {
                $moneyAgent = new MoneyAgent($this->organization);
                $context['money'] = $moneyAgent->perceive();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if (in_array('sales', $topics) || in_array('invoice', $topics) || in_array('customer', $topics)) {
            try {
                $salesAgent = new SalesAgent($this->organization);
                $context['sales'] = $salesAgent->perceive();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if (in_array('team', $topics) || in_array('people', $topics) || in_array('employee', $topics)) {
            try {
                $peopleAgent = new PeopleAgent($this->organization);
                $context['people'] = $peopleAgent->perceive();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if (in_array('inventory', $topics) || in_array('stock', $topics) || in_array('product', $topics)) {
            try {
                $inventoryAgent = new InventoryAgent($this->organization);
                $context['inventory'] = $inventoryAgent->perceive();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        // Always include insights if available
        try {
            $coreService = new AddyCoreService($this->organization);
            $thought = $coreService->getCurrentThought();
            $context['current_focus'] = $thought['state'] ?? null;
            $context['top_insight'] = $thought['top_insight'] ?? null;
        } catch (\Exception $e) {
            // Ignore
        }

        return $context;
    }

    /**
     * Get user's recent activity
     */
    public function getRecentActivity(string $userId, int $limit = 10): array
    {
        $activities = [];

        // Get recent transactions
        try {
            $transactions = \App\Models\MoneyMovement::where('organization_id', $this->organization->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($transactions as $tx) {
                $activities[] = [
                    'type' => 'transaction',
                    'description' => "{$tx->flow_type}: {$tx->description}",
                    'amount' => $tx->amount,
                    'date' => $tx->created_at->toDateTimeString(),
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Get recent invoices
        try {
            $invoices = \App\Models\Invoice::where('organization_id', $this->organization->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($invoices as $inv) {
                $activities[] = [
                    'type' => 'invoice',
                    'description' => "Invoice #{$inv->invoice_number} - {$inv->status}",
                    'amount' => $inv->total_amount,
                    'date' => $inv->created_at->toDateTimeString(),
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Sort by date
        usort($activities, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return array_slice($activities, 0, $limit);
    }

    /**
     * Search system data
     */
    public function search(string $userId, string $searchTerm, array $filters = []): array
    {
        $results = [];

        // Search customers
        try {
            $customers = \App\Models\Customer::where('organization_id', $this->organization->id)
                ->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                })
                ->limit(5)
                ->get();

            foreach ($customers as $customer) {
                $results[] = [
                    'type' => 'customer',
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Search invoices
        try {
            $invoices = \App\Models\Invoice::where('organization_id', $this->organization->id)
                ->where(function ($q) use ($searchTerm) {
                    $q->where('invoice_number', 'LIKE', "%{$searchTerm}%");
                })
                ->limit(5)
                ->get();

            foreach ($invoices as $invoice) {
                $results[] = [
                    'type' => 'invoice',
                    'id' => $invoice->id,
                    'number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'amount' => $invoice->total_amount,
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return $results;
    }

    /**
     * Get available actions in Addy
     */
    public function getAvailableActions(string $userId): array
    {
        return [
            [
                'action' => 'create_transaction',
                'description' => 'Record a new income or expense',
                'url' => '/money/movements/create',
            ],
            [
                'action' => 'create_invoice',
                'description' => 'Create a new invoice for a customer',
                'url' => '/invoices/create',
            ],
            [
                'action' => 'view_reports',
                'description' => 'View financial reports',
                'url' => '/reports',
            ],
            [
                'action' => 'manage_customers',
                'description' => 'View or add customers',
                'url' => '/customers',
            ],
            [
                'action' => 'view_insights',
                'description' => 'See Addy\'s insights and recommendations',
                'url' => '/dashboard',
            ],
        ];
    }

    /**
     * Detect topics from query
     */
    private function detectTopics(string $query): array
    {
        $topicKeywords = [
            'money' => ['cash', 'money', 'balance', 'expense', 'income', 'budget', 'spending'],
            'sales' => ['sales', 'revenue', 'invoice', 'customer', 'client', 'quote'],
            'team' => ['team', 'employee', 'staff', 'payroll', 'leave', 'people'],
            'inventory' => ['inventory', 'stock', 'product', 'item', 'warehouse'],
        ];

        $detected = [];
        foreach ($topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($query, $keyword)) {
                    $detected[] = $topic;
                    break;
                }
            }
        }

        return array_unique($detected);
    }
}


