<?php

namespace App\Http\Controllers;

use App\Modules\Retail\Models\Sale;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Prospect;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class SalesController extends Controller
{
    /**
     * Helper to get the current organization ID
     */
    protected function getOrganizationId(): ?string
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $currentOrgId = session('current_organization_id');
        if ($currentOrgId) {
            $org = $user->organizations()->where('organizations.id', $currentOrgId)->first();
            if ($org) {
                return $org->id;
            }
        }
        
        if ($user->attributes['organization_id'] ?? null) {
            $org = $user->organizations()->where('organizations.id', $user->attributes['organization_id'])->first();
            if ($org) {
                return $org->id;
            }
        }
        
        return $user->organizations()->first()?->id;
    }

    public function index(Request $request)
    {
        try {
            $organizationId = $this->getOrganizationId();
            if (!$organizationId) {
                abort(403, 'You must belong to an organization to access sales.');
            }
            
            // Calculate stats
            $customerMetrics = $this->getCustomerMetrics($organizationId);
            
            $thisMonthStart = Carbon::now()->startOfMonth();
            
            $monthlySales = 0;
            try {
                $monthlySales = Sale::where('organization_id', $organizationId)
                    ->where('created_at', '>=', $thisMonthStart)
                    ->sum('total_amount') ?? 0;
            } catch (\Exception $e) {
                \Log::warning('Failed to get monthly sales', ['error' => $e->getMessage()]);
            }
            
            $pendingInvoices = 0;
            try {
                $pendingInvoices = Invoice::where('organization_id', $organizationId)
                    ->where('status', '!=', 'paid')
                    ->whereRaw('total_amount > paid_amount')
                    ->count();
            } catch (\Exception $e) {
                \Log::warning('Failed to get pending invoices', ['error' => $e->getMessage()]);
            }
            
            // Get prospects metrics
            $prospectMetrics = $this->getProspectMetrics($organizationId);
            
            // Get quotation metrics (merged with quotes)
            $quotationMetrics = $this->getQuotationMetrics($organizationId);
            
            // Get projected income from pending invoices
            $projectedIncome = $this->getProjectedIncome($organizationId);
            
            // Get customer personas
            $customerPersonas = $this->getCustomerPersonas($organizationId);
            
            // Get income timeline
            $incomeTimeline = $this->getIncomeTimeline($organizationId);
            
            // Get recent sales
            $recentSales = [];
            try {
                $recentSales = Sale::where('organization_id', $organizationId)
                    ->with('customer')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($sale) {
                        return [
                            'id' => $sale->id,
                            'sale_number' => $sale->sale_number ?? 'N/A',
                            'total_amount' => $sale->total_amount ?? 0,
                            'created_at' => $sale->created_at?->toDateTimeString() ?? now()->toDateTimeString(),
                            'customer' => $sale->customer ? [
                                'name' => $sale->customer->name ?? $sale->customer_name ?? 'Unknown',
                            ] : ($sale->customer_name ? ['name' => $sale->customer_name] : null),
                        ];
                    });
            } catch (\Exception $e) {
                \Log::warning('Failed to get recent sales', ['error' => $e->getMessage()]);
            }
            
            // Get Sales-specific insights
            $insights = [];
            try {
                $insights = AddyInsight::active($organizationId)
                    ->where('category', 'sales')
                    ->orderBy('priority', 'desc')
                    ->limit(3)
                    ->get()
                    ->map(fn($insight) => [
                        'id' => $insight->id,
                        'type' => $insight->type,
                        'title' => $insight->title,
                        'description' => $insight->description,
                        'priority' => (float) $insight->priority,
                        'is_actionable' => $insight->is_actionable,
                        'action_url' => $insight->action_url,
                    ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to get insights', ['error' => $e->getMessage()]);
            }

            return Inertia::render('Sales/Index', [
                'stats' => [
                    'total_customers' => $customerMetrics['total'] ?? 0,
                    'monthly_sales' => $monthlySales,
                    'pending_invoices' => $pendingInvoices,
                    'pending_quotations' => $quotationMetrics['pending'] ?? 0,
                    'recent_sales' => $recentSales,
                ],
                'customerMetrics' => $customerMetrics,
                'prospectMetrics' => $prospectMetrics,
                'quotationMetrics' => $quotationMetrics,
                'projectedIncome' => $projectedIncome,
                'customerPersonas' => $customerPersonas,
                'incomeTimeline' => $incomeTimeline,
                'insights' => $insights,
            ]);
        } catch (\Exception $e) {
            \Log::error('SalesController index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function getCustomerMetrics(string $organizationId): array
    {
        $customers = Customer::where('organization_id', $organizationId);

        $totalCustomers = $customers->count();
        $activeCustomers = $customers->where('status', 'active')->count();
        
        $newThisMonth = Customer::where('organization_id', $organizationId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalLifetimeValue = $customers->sum('lifetime_value');
        $outstandingBalance = $customers->sum('outstanding_balance');

        return [
            'total' => $totalCustomers,
            'active' => $activeCustomers,
            'new_this_month' => $newThisMonth,
            'lifetime_value' => $totalLifetimeValue,
            'outstanding_balance' => $outstandingBalance,
        ];
    }

    private function getProjectedIncome(string $organizationId): array
    {
        $pendingInvoices = Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->get();

        $total = $pendingInvoices->sum('amount_due');

        // Group by timeframe - ensure due_date is a Carbon instance
        $thisWeek = $pendingInvoices
            ->filter(function($invoice) {
                if (!$invoice->due_date) return false;
                $dueDate = $invoice->due_date instanceof \Carbon\Carbon ? $invoice->due_date : \Carbon\Carbon::parse($invoice->due_date);
                return $dueDate->between(now(), now()->addWeek());
            })
            ->sum('amount_due');

        $thisMonth = $pendingInvoices
            ->filter(function($invoice) {
                if (!$invoice->due_date) return false;
                $dueDate = $invoice->due_date instanceof \Carbon\Carbon ? $invoice->due_date : \Carbon\Carbon::parse($invoice->due_date);
                return $dueDate->between(now(), now()->addMonth());
            })
            ->sum('amount_due');

        $thisQuarter = $pendingInvoices
            ->filter(function($invoice) {
                if (!$invoice->due_date) return false;
                $dueDate = $invoice->due_date instanceof \Carbon\Carbon ? $invoice->due_date : \Carbon\Carbon::parse($invoice->due_date);
                return $dueDate->between(now(), now()->addMonths(3));
            })
            ->sum('amount_due');

        $overdue = $pendingInvoices
            ->filter(function($invoice) {
                if (!$invoice->due_date) return false;
                $dueDate = $invoice->due_date instanceof \Carbon\Carbon ? $invoice->due_date : \Carbon\Carbon::parse($invoice->due_date);
                return $invoice->is_overdue ?? $dueDate->isPast();
            })
            ->sum('amount_due');

        return [
            'total' => $total,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
            'this_quarter' => $thisQuarter,
            'overdue' => $overdue,
            'invoice_count' => $pendingInvoices->count(),
        ];
    }

    private function getCustomerPersonas(string $organizationId): array
    {
        try {
            if (!class_exists(\App\Models\CustomerPersona::class)) {
                return [];
            }
            
            return \App\Models\CustomerPersona::where('organization_id', $organizationId)
                ->where('is_active', true)
                ->withCount('customers')
                ->get()
                ->map(function ($persona) {
                    return [
                        'id' => $persona->id,
                        'name' => $persona->name ?? 'Unknown',
                        'description' => $persona->description ?? '',
                        'industry' => $persona->industry ?? '',
                        'size' => $persona->size ?? 'medium',
                        'payment_behavior' => $persona->payment_behavior ?? 'average',
                        'color' => $persona->color ?? '#3b82f6',
                        'icon' => $persona->icon ?? 'users',
                        'customer_count' => $persona->customers_count ?? 0,
                        'total_revenue' => $persona->total_revenue ?? 0,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            \Log::warning('Failed to load customer personas', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getIncomeTimeline(string $organizationId): array
    {
        $invoices = Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->whereNotNull('due_date')
            ->orderBy('due_date', 'asc')
            ->get();

        // Group by week for the next 12 weeks
        $timeline = [];
        for ($i = 0; $i < 12; $i++) {
            $weekStart = now()->addWeeks($i)->startOfWeek();
            $weekEnd = now()->addWeeks($i)->endOfWeek();
            
            $weekInvoices = $invoices->filter(function ($invoice) use ($weekStart, $weekEnd) {
                if (!$invoice->due_date) return false;
                $dueDate = $invoice->due_date instanceof \Carbon\Carbon ? $invoice->due_date : \Carbon\Carbon::parse($invoice->due_date);
                return $dueDate->between($weekStart, $weekEnd);
            });

            $timeline[] = [
                'week' => $weekStart->format('M d'),
                'amount' => $weekInvoices->sum('amount_due'),
                'invoice_count' => $weekInvoices->count(),
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
            ];
        }

        return $timeline;
    }

    private function getProspectMetrics(string $organizationId): array
    {
        try {
            // Use 'stage' instead of 'status' - prospects use stages like 'new', 'contacted', 'qualified', 'converted'
            $totalProspects = Prospect::where('organization_id', $organizationId)->count();
            
            $activeProspects = Prospect::where('organization_id', $organizationId)
                ->whereIn('stage', ['new', 'contacted', 'qualified'])
                ->count();
            
            $newThisMonth = Prospect::where('organization_id', $organizationId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $convertedThisMonth = Prospect::where('organization_id', $organizationId)
                ->where('stage', 'converted')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count();

            return [
                'total' => $totalProspects,
                'active' => $activeProspects,
                'new_this_month' => $newThisMonth,
                'converted_this_month' => $convertedThisMonth,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get prospect metrics', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return [
                'total' => 0,
                'active' => 0,
                'new_this_month' => 0,
                'converted_this_month' => 0,
            ];
        }
    }

    private function getQuotationMetrics(string $organizationId): array
    {
        try {
            $quotations = Quote::where('organization_id', $organizationId);

            $pending = $quotations->whereIn('status', ['sent', 'pending', 'draft'])->count();
            $totalValue = $quotations->whereIn('status', ['sent', 'pending', 'draft'])->sum('total_amount') ?? 0;
            
            $acceptedThisMonth = Quote::where('organization_id', $organizationId)
                ->where('status', 'accepted')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count();

            $sentThisMonth = Quote::where('organization_id', $organizationId)
                ->whereMonth('quote_date', now()->month)
                ->whereYear('quote_date', now()->year)
                ->count();

            $conversionRate = $sentThisMonth > 0 
                ? round(($acceptedThisMonth / $sentThisMonth) * 100, 1)
                : 0;

            return [
                'pending' => $pending,
                'total_value' => $totalValue,
                'conversion_rate' => $conversionRate,
                'accepted_this_month' => $acceptedThisMonth,
            ];
        } catch (\Exception $e) {
            \Log::warning('Failed to get quotation metrics', ['error' => $e->getMessage()]);
            return [
                'pending' => 0,
                'total_value' => 0,
                'conversion_rate' => 0,
                'accepted_this_month' => 0,
            ];
        }
    }
}

