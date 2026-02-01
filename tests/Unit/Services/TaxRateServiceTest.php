<?php

namespace Tests\Unit\Services;

use App\Models\Organization;
use App\Modules\Tax\Models\TaxRate;
use App\Services\TaxRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaxRateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TaxRateService $service;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxRateService();
        
        // Create a test organization with unique slug
        $this->organization = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Organization',
            'slug' => 'test-org-' . Str::random(8),
            'currency' => 'ZMW',
            'country' => 'ZM',
        ]);
    }

    /** @test */
    public function it_returns_supported_countries(): void
    {
        $countries = $this->service->getSupportedCountries();

        $this->assertIsArray($countries);
        $this->assertNotEmpty($countries);
        
        // Check that Zambia is included
        $zambia = collect($countries)->firstWhere('code', 'ZM');
        $this->assertNotNull($zambia);
        $this->assertEquals('Zambia', $zambia['name']);
        $this->assertEquals('ZMW', $zambia['currency']);
        $this->assertEquals('VAT', $zambia['tax_type']);
    }

    /** @test */
    public function it_returns_zambia_tax_rates(): void
    {
        $rates = $this->service->getCountryRates('ZM');

        $this->assertIsArray($rates);
        $this->assertEquals('Zambia', $rates['country']);
        $this->assertEquals('ZMW', $rates['currency']);
        $this->assertEquals('VAT', $rates['tax_type']);
        
        // Check standard VAT rate
        $standardVat = collect($rates['rates'])->firstWhere('code', 'A');
        $this->assertNotNull($standardVat);
        $this->assertEquals(16.00, $standardVat['rate']);
    }

    /** @test */
    public function it_returns_empty_for_unsupported_country(): void
    {
        $rates = $this->service->getCountryRates('XX');

        $this->assertEmpty($rates);
    }

    /** @test */
    public function it_auto_populates_tax_rates_for_zambia(): void
    {
        $result = $this->service->autoPopulate($this->organization, 'ZM');

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['created']);
        $this->assertEquals('Zambia', $result['country']);

        // Check that rates were created in database
        $rates = TaxRate::where('organization_id', $this->organization->id)->get();
        $this->assertNotEmpty($rates);
        
        // Check standard VAT was created
        $standardVat = $rates->firstWhere('code', 'A');
        $this->assertNotNull($standardVat);
        $this->assertEquals(16.00, $standardVat->rate);
    }

    /** @test */
    public function it_does_not_duplicate_rates_on_second_auto_populate(): void
    {
        // First populate
        $result1 = $this->service->autoPopulate($this->organization, 'ZM');
        $firstCount = $result1['created'];

        // Second populate
        $result2 = $this->service->autoPopulate($this->organization, 'ZM');

        $this->assertTrue($result2['success']);
        $this->assertEquals(0, $result2['created']);

        // Count should remain the same
        $totalRates = TaxRate::where('organization_id', $this->organization->id)->count();
        $this->assertEquals($firstCount, $totalRates);
    }

    /** @test */
    public function it_returns_error_for_unsupported_country_auto_populate(): void
    {
        $result = $this->service->autoPopulate($this->organization, 'XX');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No tax rates available', $result['message']);
    }

    /** @test */
    public function it_calculates_tax_summary(): void
    {
        $summary = $this->service->getTaxSummary($this->organization);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('tax_collected', $summary);
        $this->assertArrayHasKey('tax_paid', $summary);
        $this->assertArrayHasKey('net_liability', $summary);
        $this->assertArrayHasKey('period', $summary);
        $this->assertArrayHasKey('currency', $summary);
    }

    /** @test */
    public function it_gets_south_africa_rates(): void
    {
        $rates = $this->service->getCountryRates('ZA');

        $this->assertEquals('South Africa', $rates['country']);
        $this->assertEquals('ZAR', $rates['currency']);
        
        $standardVat = collect($rates['rates'])->firstWhere('code', 'STD');
        $this->assertNotNull($standardVat);
        $this->assertEquals(15.00, $standardVat['rate']);
    }

    /** @test */
    public function it_gets_uk_rates(): void
    {
        $rates = $this->service->getCountryRates('GB');

        $this->assertEquals('United Kingdom', $rates['country']);
        $this->assertEquals('GBP', $rates['currency']);
        
        $standardVat = collect($rates['rates'])->firstWhere('code', 'STD');
        $this->assertNotNull($standardVat);
        $this->assertEquals(20.00, $standardVat['rate']);
    }

    /** @test */
    public function it_calculates_tax_correctly(): void
    {
        // Create a tax rate
        $taxRate = TaxRate::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'name' => 'Standard VAT',
            'code' => 'STD',
            'rate' => 16.00,
            'is_active' => true,
            'is_default' => true,
        ]);

        $taxAmount = $this->service->calculateTax(100.00, $taxRate);

        $this->assertEquals(16.00, $taxAmount);
    }

    /** @test */
    public function it_gets_default_rate(): void
    {
        // Create rates
        TaxRate::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'name' => 'Zero Rate',
            'code' => 'ZR',
            'rate' => 0.00,
            'is_active' => true,
            'is_default' => false,
        ]);

        $defaultRate = TaxRate::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'name' => 'Standard VAT',
            'code' => 'STD',
            'rate' => 16.00,
            'is_active' => true,
            'is_default' => true,
        ]);

        $rate = $this->service->getDefaultRate($this->organization);

        $this->assertNotNull($rate);
        $this->assertEquals($defaultRate->id, $rate->id);
        $this->assertEquals(16.00, $rate->rate);
    }
}
