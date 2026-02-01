<?php

namespace App\Services;

use App\Models\Organization;
use App\Modules\Tax\Models\TaxRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service for managing tax rates and country-specific tax data.
 * 
 * Provides:
 * - Country tax rate lookups
 * - Auto-population of standard tax rates
 * - Tax calculation helpers
 */
class TaxRateService
{
    /**
     * Country tax rate definitions
     * 
     * @var array<string, array>
     */
    protected array $countryRates = [
        'ZM' => [
            'name' => 'Zambia',
            'currency' => 'ZMW',
            'tax_type' => 'VAT',
            'rates' => [
                ['name' => 'Standard VAT', 'code' => 'A', 'rate' => 16.00, 'description' => 'Standard rated goods and services'],
                ['name' => 'Zero-rated (Exports)', 'code' => 'C1', 'rate' => 0.00, 'description' => 'Export goods and services'],
                ['name' => 'Zero-rated (LPO)', 'code' => 'C2', 'rate' => 0.00, 'description' => 'Local Purchase Orders'],
                ['name' => 'Zero-rated (Nature)', 'code' => 'C3', 'rate' => 0.00, 'description' => 'Zero-rated by nature'],
                ['name' => 'Exempt', 'code' => 'D', 'rate' => 0.00, 'description' => 'VAT exempt goods and services'],
                ['name' => 'Tourism Levy', 'code' => 'TL', 'rate' => 1.50, 'description' => 'Tourism levy on accommodation'],
            ],
        ],
        'ZA' => [
            'name' => 'South Africa',
            'currency' => 'ZAR',
            'tax_type' => 'VAT',
            'rates' => [
                ['name' => 'Standard VAT', 'code' => 'STD', 'rate' => 15.00, 'description' => 'Standard rated goods and services'],
                ['name' => 'Zero-rated', 'code' => 'ZR', 'rate' => 0.00, 'description' => 'Zero-rated supplies'],
                ['name' => 'Exempt', 'code' => 'EX', 'rate' => 0.00, 'description' => 'VAT exempt supplies'],
            ],
        ],
        'KE' => [
            'name' => 'Kenya',
            'currency' => 'KES',
            'tax_type' => 'VAT',
            'rates' => [
                ['name' => 'Standard VAT', 'code' => 'STD', 'rate' => 16.00, 'description' => 'Standard rated goods and services'],
                ['name' => 'Reduced Rate', 'code' => 'RED', 'rate' => 8.00, 'description' => 'Reduced rate for petroleum'],
                ['name' => 'Zero-rated', 'code' => 'ZR', 'rate' => 0.00, 'description' => 'Zero-rated supplies'],
                ['name' => 'Exempt', 'code' => 'EX', 'rate' => 0.00, 'description' => 'VAT exempt supplies'],
            ],
        ],
        'GB' => [
            'name' => 'United Kingdom',
            'currency' => 'GBP',
            'tax_type' => 'VAT',
            'rates' => [
                ['name' => 'Standard VAT', 'code' => 'STD', 'rate' => 20.00, 'description' => 'Standard rated goods and services'],
                ['name' => 'Reduced Rate', 'code' => 'RED', 'rate' => 5.00, 'description' => 'Reduced rate for certain goods'],
                ['name' => 'Zero-rated', 'code' => 'ZR', 'rate' => 0.00, 'description' => 'Zero-rated supplies'],
                ['name' => 'Exempt', 'code' => 'EX', 'rate' => 0.00, 'description' => 'VAT exempt supplies'],
            ],
        ],
        'US' => [
            'name' => 'United States',
            'currency' => 'USD',
            'tax_type' => 'Sales Tax',
            'rates' => [
                // US sales tax varies by state - provide common example
                ['name' => 'No Federal Sales Tax', 'code' => 'FED', 'rate' => 0.00, 'description' => 'No federal sales tax - rates vary by state'],
            ],
            'note' => 'Sales tax in the US varies by state, county, and city. Configure your specific rates manually.',
        ],
        'NG' => [
            'name' => 'Nigeria',
            'currency' => 'NGN',
            'tax_type' => 'VAT',
            'rates' => [
                ['name' => 'Standard VAT', 'code' => 'STD', 'rate' => 7.50, 'description' => 'Standard rated goods and services'],
                ['name' => 'Exempt', 'code' => 'EX', 'rate' => 0.00, 'description' => 'VAT exempt supplies'],
            ],
        ],
        'GH' => [
            'name' => 'Ghana',
            'currency' => 'GHS',
            'tax_type' => 'VAT',
            'rates' => [
                ['name' => 'Standard VAT', 'code' => 'STD', 'rate' => 15.00, 'description' => 'Standard rated goods and services'],
                ['name' => 'Zero-rated', 'code' => 'ZR', 'rate' => 0.00, 'description' => 'Zero-rated supplies'],
                ['name' => 'Exempt', 'code' => 'EX', 'rate' => 0.00, 'description' => 'VAT exempt supplies'],
            ],
        ],
        'BW' => [
            'name' => 'Botswana',
            'currency' => 'BWP',
            'tax_type' => 'VAT',
            'rates' => [
                ['name' => 'Standard VAT', 'code' => 'STD', 'rate' => 14.00, 'description' => 'Standard rated goods and services'],
                ['name' => 'Zero-rated', 'code' => 'ZR', 'rate' => 0.00, 'description' => 'Zero-rated supplies'],
                ['name' => 'Exempt', 'code' => 'EX', 'rate' => 0.00, 'description' => 'VAT exempt supplies'],
            ],
        ],
    ];

    /**
     * Get all supported countries
     */
    public function getSupportedCountries(): array
    {
        return collect($this->countryRates)->map(function ($data, $code) {
            return [
                'code' => $code,
                'name' => $data['name'],
                'currency' => $data['currency'],
                'tax_type' => $data['tax_type'],
                'rate_count' => count($data['rates']),
            ];
        })->values()->toArray();
    }

    /**
     * Get tax rates for a specific country
     */
    public function getCountryRates(string $countryCode): array
    {
        $country = $this->countryRates[strtoupper($countryCode)] ?? null;
        
        if (!$country) {
            return [];
        }

        return [
            'country' => $country['name'],
            'currency' => $country['currency'],
            'tax_type' => $country['tax_type'],
            'rates' => $country['rates'],
            'note' => $country['note'] ?? null,
        ];
    }

    /**
     * Auto-populate tax rates for an organization based on country
     */
    public function autoPopulate(Organization $organization, string $countryCode): array
    {
        $country = $this->countryRates[strtoupper($countryCode)] ?? null;

        if (!$country) {
            return [
                'success' => false,
                'message' => "No tax rates available for country: {$countryCode}",
                'created' => 0,
            ];
        }

        $created = 0;

        DB::transaction(function () use ($organization, $country, &$created) {
            foreach ($country['rates'] as $rate) {
                // Check if rate already exists
                $exists = TaxRate::where('organization_id', $organization->id)
                    ->where('code', $rate['code'])
                    ->exists();

                if (!$exists) {
                    TaxRate::create([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $organization->id,
                        'name' => $rate['name'],
                        'code' => $rate['code'],
                        'rate' => $rate['rate'],
                        'description' => $rate['description'],
                        'tax_type' => $country['tax_type'],
                        'is_default' => $rate['code'] === 'A' || $rate['code'] === 'STD',
                        'is_active' => true,
                        'metadata' => [
                            'country' => $country['name'],
                            'auto_populated' => true,
                        ],
                    ]);
                    $created++;
                }
            }
        });

        return [
            'success' => true,
            'message' => "Created {$created} tax rate(s) for {$country['name']}",
            'created' => $created,
            'country' => $country['name'],
            'tax_type' => $country['tax_type'],
        ];
    }

    /**
     * Get tax summary for an organization within a date range
     */
    public function getTaxSummary(Organization $organization, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        // Tax collected from invoices
        $taxCollected = DB::table('invoices')
            ->where('organization_id', $organization->id)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->sum('tax_amount');

        // Tax paid on bills/expenses (if bill model exists)
        $taxPaid = 0;
        if (class_exists(\App\Models\Bill::class)) {
            $taxPaid = DB::table('bills')
                ->where('organization_id', $organization->id)
                ->whereBetween('bill_date', [$startDate, $endDate])
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->sum('tax_amount');
        }

        // Also check money_movements for expenses with tax (if column exists)
        try {
            $expenseTax = DB::table('money_movements')
                ->where('organization_id', $organization->id)
                ->where('type', 'expense')
                ->whereBetween('date', [$startDate, $endDate])
                ->sum(DB::raw('COALESCE(tax_amount, 0)'));

            $taxPaid += $expenseTax;
        } catch (\Exception $e) {
            // Column may not exist in some environments
        }

        $netLiability = $taxCollected - $taxPaid;

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'tax_collected' => round($taxCollected, 2),
            'tax_paid' => round($taxPaid, 2),
            'net_liability' => round($netLiability, 2),
            'currency' => $organization->currency ?? 'ZMW',
        ];
    }

    /**
     * Get default tax rate for organization
     */
    public function getDefaultRate(Organization $organization): ?TaxRate
    {
        return TaxRate::where('organization_id', $organization->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Calculate tax amount for a given amount
     */
    public function calculateTax(float $amount, TaxRate $taxRate): float
    {
        return round($amount * ($taxRate->rate / 100), 2);
    }
}
