<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Modules\Tax\Models\TaxRate;
use App\Services\TaxRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaxController extends Controller
{
    protected TaxRateService $taxRateService;

    public function __construct(TaxRateService $taxRateService)
    {
        $this->taxRateService = $taxRateService;
    }

    /**
     * Get current organization ID
     */
    protected function getOrganizationId()
    {
        $user = Auth::user();
        $currentOrgId = session('current_organization_id') ?? $user->current_organization_id;
        
        if ($currentOrgId) {
            return $currentOrgId;
        }
        
        return $user->organizations()->first()?->id;
    }

    /**
     * Tax Overview page
     */
    public function index()
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to access tax settings.');
        }

        $organization = Organization::find($organizationId);
        
        // Get tax rates for this organization
        $taxRates = TaxRate::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        // Get tax summary
        $taxSummary = $this->taxRateService->getTaxSummary($organization);

        // Get supported countries
        $supportedCountries = $this->taxRateService->getSupportedCountries();

        // Check module status
        $modules = [
            'smart_invoice_enabled' => $this->isModuleEnabled('smart-invoice'),
            'tax_enabled' => $this->isModuleEnabled('tax'),
        ];

        return Inertia::render('Tax/Index', [
            'organization' => $organization,
            'taxRates' => $taxRates,
            'taxSummary' => $taxSummary,
            'supportedCountries' => $supportedCountries,
            'currentCountry' => $organization->country ?? 'ZM',
            'modules' => $modules,
        ]);
    }

    /**
     * Tax Reports page
     */
    public function reports(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to view tax reports.');
        }

        $organization = Organization::find($organizationId);
        $period = $request->get('period', 'this_month');

        // Calculate date range based on period
        $dateRange = $this->getDateRange($period);

        // Get tax summary for the period
        $taxSummary = $this->taxRateService->getTaxSummary(
            $organization, 
            $dateRange['start'], 
            $dateRange['end']
        );

        // Get tax transactions (invoices and expenses with tax)
        $taxTransactions = $this->getTaxTransactions($organizationId, $dateRange);

        return Inertia::render('Reports/Tax', [
            'organization' => $organization,
            'taxSummary' => $taxSummary,
            'taxTransactions' => $taxTransactions,
            'period' => $period,
            'periodLabel' => $dateRange['label'],
        ]);
    }

    /**
     * Auto-populate tax rates from country
     */
    public function autoPopulate(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }

        $validated = $request->validate([
            'country_code' => 'required|string|size:2',
        ]);

        $organization = Organization::find($organizationId);
        $result = $this->taxRateService->autoPopulate($organization, $validated['country_code']);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['country_code' => $result['message']]);
    }

    /**
     * Export tax report
     */
    public function export(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }

        $organization = Organization::find($organizationId);
        $period = $request->get('period', 'this_month');
        $format = $request->get('format', 'csv');

        $dateRange = $this->getDateRange($period);
        $taxSummary = $this->taxRateService->getTaxSummary(
            $organization, 
            $dateRange['start'], 
            $dateRange['end']
        );
        $taxTransactions = $this->getTaxTransactions($organizationId, $dateRange);

        if ($format === 'csv') {
            return $this->exportCsv($taxTransactions, $taxSummary, $organization, $dateRange);
        }

        // For PDF, redirect to a PDF view (to be implemented)
        return back()->with('info', 'PDF export coming soon.');
    }

    /**
     * Export as CSV
     */
    protected function exportCsv($transactions, $summary, $organization, $dateRange)
    {
        $filename = "tax-report-{$dateRange['start']}-to-{$dateRange['end']}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions, $summary, $organization, $dateRange) {
            $handle = fopen('php://output', 'w');
            
            // Header
            fputcsv($handle, ['Tax Report']);
            fputcsv($handle, ['Organization:', $organization->name]);
            fputcsv($handle, ['Period:', $dateRange['start'] . ' to ' . $dateRange['end']]);
            fputcsv($handle, []);
            
            // Summary
            fputcsv($handle, ['Summary']);
            fputcsv($handle, ['Tax Collected:', $summary['tax_collected']]);
            fputcsv($handle, ['Tax Paid:', $summary['tax_paid']]);
            fputcsv($handle, ['Net Liability:', $summary['net_liability']]);
            fputcsv($handle, []);
            
            // Transactions
            fputcsv($handle, ['Transactions']);
            fputcsv($handle, ['Date', 'Description', 'Reference', 'Type', 'Tax Rate', 'Tax Amount']);
            
            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx['date'],
                    $tx['description'],
                    $tx['reference'],
                    $tx['type'],
                    $tx['tax_rate'] . '%',
                    $tx['tax_amount'],
                ]);
            }
            
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get date range from period string
     */
    protected function getDateRange(string $period): array
    {
        $now = now();
        
        return match ($period) {
            'this_month' => [
                'start' => $now->copy()->startOfMonth()->toDateString(),
                'end' => $now->copy()->endOfMonth()->toDateString(),
                'label' => $now->format('F Y'),
            ],
            'last_month' => [
                'start' => $now->copy()->subMonth()->startOfMonth()->toDateString(),
                'end' => $now->copy()->subMonth()->endOfMonth()->toDateString(),
                'label' => $now->copy()->subMonth()->format('F Y'),
            ],
            'this_quarter' => [
                'start' => $now->copy()->startOfQuarter()->toDateString(),
                'end' => $now->copy()->endOfQuarter()->toDateString(),
                'label' => 'Q' . $now->quarter . ' ' . $now->year,
            ],
            'last_quarter' => [
                'start' => $now->copy()->subQuarter()->startOfQuarter()->toDateString(),
                'end' => $now->copy()->subQuarter()->endOfQuarter()->toDateString(),
                'label' => 'Q' . $now->copy()->subQuarter()->quarter . ' ' . $now->copy()->subQuarter()->year,
            ],
            'this_year' => [
                'start' => $now->copy()->startOfYear()->toDateString(),
                'end' => $now->copy()->endOfYear()->toDateString(),
                'label' => $now->year,
            ],
            'last_year' => [
                'start' => $now->copy()->subYear()->startOfYear()->toDateString(),
                'end' => $now->copy()->subYear()->endOfYear()->toDateString(),
                'label' => $now->copy()->subYear()->year,
            ],
            default => [
                'start' => $now->copy()->startOfMonth()->toDateString(),
                'end' => $now->copy()->endOfMonth()->toDateString(),
                'label' => 'This Month',
            ],
        };
    }

    /**
     * Get tax transactions for a period
     */
    protected function getTaxTransactions(string $organizationId, array $dateRange): array
    {
        $transactions = [];

        // Get invoices with tax
        $invoices = \App\Models\Invoice::where('organization_id', $organizationId)
            ->whereBetween('invoice_date', [$dateRange['start'], $dateRange['end']])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->where('tax_amount', '>', 0)
            ->with('customer')
            ->orderBy('invoice_date', 'desc')
            ->get();

        foreach ($invoices as $invoice) {
            $transactions[] = [
                'date' => $invoice->invoice_date->toDateString(),
                'description' => 'Invoice to ' . ($invoice->customer->name ?? 'Customer'),
                'reference' => $invoice->invoice_number,
                'type' => 'collected',
                'tax_rate' => $invoice->subtotal > 0 
                    ? round(($invoice->tax_amount / $invoice->subtotal) * 100, 1) 
                    : 0,
                'tax_amount' => (float) $invoice->tax_amount,
            ];
        }

        // Get expenses with tax (from money_movements)
        $expenses = \App\Models\MoneyMovement::where('organization_id', $organizationId)
            ->where('type', 'expense')
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('tax_amount')
            ->where('tax_amount', '>', 0)
            ->orderBy('date', 'desc')
            ->get();

        foreach ($expenses as $expense) {
            $transactions[] = [
                'date' => $expense->date->toDateString(),
                'description' => $expense->description ?? 'Expense',
                'reference' => $expense->reference ?? '-',
                'type' => 'paid',
                'tax_rate' => $expense->amount > 0 
                    ? round(($expense->tax_amount / $expense->amount) * 100, 1) 
                    : 0,
                'tax_amount' => (float) $expense->tax_amount,
            ];
        }

        // Sort by date descending
        usort($transactions, fn($a, $b) => strcmp($b['date'], $a['date']));

        return $transactions;
    }

    /**
     * Check if a module is enabled
     */
    protected function isModuleEnabled(string $moduleAlias): bool
    {
        try {
            $modulePath = base_path("app/Modules/" . str_replace('-', '', ucwords($moduleAlias, '-')) . "/module.json");
            
            if (file_exists($modulePath)) {
                $moduleConfig = json_decode(file_get_contents($modulePath), true);
                return $moduleConfig['enabled'] ?? false;
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        return false;
    }
}
