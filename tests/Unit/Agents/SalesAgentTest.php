<?php

namespace Tests\Unit\Agents;

use Tests\TestCase;
use App\Services\Addy\Agents\SalesAgent;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Payment;

class SalesAgentTest extends TestCase
{
    protected SalesAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new SalesAgent($this->testOrganization);
    }

    /** @test */
    public function it_perceives_customer_stats_correctly(): void
    {
        Customer::factory()->count(5)->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        Customer::factory()->count(2)->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $perception = $this->agent->perceive();

        $this->assertEquals(7, $perception['customer_stats']['total']);
        // Note: Customer model doesn't have status field, so active/inactive will be same as total
    }

    /** @test */
    public function it_detects_overdue_invoices(): void
    {
        // Use different dates to ensure different invoice number prefixes
        $date1 = now()->format('Ymd');
        $date2 = now()->addDay()->format('Ymd');
        
        // Create overdue invoice with manual invoice number
        $invoice1 = new \App\Models\Invoice([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => \App\Models\Customer::factory()->create(['organization_id' => $this->testOrganization->id])->id,
            'status' => 'sent',
            'due_date' => now()->subDays(5),
            'total_amount' => 1000,
            'subtotal' => 862.07,
            'tax_amount' => 137.93,
            'invoice_date' => now(),
            'invoice_number' => "INV-{$date1}-0001",
        ]);
        $invoice1->save();

        // Create pending invoice (not overdue) with different invoice number
        $invoice2 = new \App\Models\Invoice([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => \App\Models\Customer::factory()->create(['organization_id' => $this->testOrganization->id])->id,
            'status' => 'sent',
            'due_date' => now()->addDays(5),
            'total_amount' => 500,
            'subtotal' => 431.03,
            'tax_amount' => 68.97,
            'invoice_date' => now(),
            'invoice_number' => "INV-{$date2}-0001",
        ]);
        $invoice2->save();

        // Refresh to get updated status (may have been changed to 'overdue' by booted event)
        $invoice1->refresh();
        
        $perception = $this->agent->perceive();

        // SalesAgent now checks for status 'overdue' OR (status 'sent' and due_date < now())
        $this->assertEquals(1, $perception['invoice_health']['overdue_count']);
        $this->assertEquals(1000, $perception['invoice_health']['overdue_amount']);
        $this->assertEquals(1, $perception['invoice_health']['pending_count']);
        $this->assertEquals(500, $perception['invoice_health']['pending_amount']);
    }

    /** @test */
    public function it_calculates_sales_performance_trend(): void
    {
        // Use a unique organization to avoid conflicts with other tests
        $uniqueOrg = $this->createOtherOrganization();
        $uniqueAgent = new \App\Services\Addy\Agents\SalesAgent($uniqueOrg);
        
        $customer = \App\Models\Customer::factory()->create([
            'organization_id' => $uniqueOrg->id,
        ]);
        
        // Last month: $5000 - use a specific date in the past month
        $lastMonthDate = now()->subMonth()->startOfMonth()->addDays(15);
        $lastMonthDateStr = $lastMonthDate->format('Ymd');
        $invoice1 = new \App\Models\Invoice([
            'organization_id' => $uniqueOrg->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 5000,
            'subtotal' => 4310.34,
            'tax_amount' => 689.66,
            'invoice_date' => $lastMonthDate,
            'created_at' => $lastMonthDate,
            'invoice_number' => "INV-{$lastMonthDateStr}-9999",
        ]);
        $invoice1->save();

        // This month: $8000 (60% increase) - use a specific date in current month
        $thisMonthDate = now()->startOfMonth()->addDays(15);
        $thisMonthDateStr = $thisMonthDate->format('Ymd');
        $invoice2 = new \App\Models\Invoice([
            'organization_id' => $uniqueOrg->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 8000,
            'subtotal' => 6896.55,
            'tax_amount' => 1103.45,
            'invoice_date' => $thisMonthDate,
            'created_at' => $thisMonthDate,
            'invoice_number' => "INV-{$thisMonthDateStr}-9999",
        ]);
        $invoice2->save();

        $perception = $uniqueAgent->perceive();

        // Verify calculations with clean organization
        $this->assertEquals(8000, $perception['sales_performance']['current_month']);
        $this->assertEquals(5000, $perception['sales_performance']['last_month']);
        $this->assertEquals('increasing', $perception['sales_performance']['trend']);
        $this->assertEquals(60, $perception['sales_performance']['change_percentage']);
    }

    /** @test */
    public function it_calculates_quote_conversion_rate(): void
    {
        // Clear any existing quotes for this organization to ensure clean test
        \App\Models\Quote::where('organization_id', $this->testOrganization->id)->delete();
        
        $date = now()->format('Ymd');
        
        // Create quotes with manual quote numbers to avoid conflicts
        // Quote format is "QUOTE-{date}-{number}"
        // Use high starting numbers to avoid conflicts with any existing quotes
        for ($i = 9001; $i <= 9003; $i++) {
            Quote::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'status' => 'accepted',
                'created_at' => now(),
                'quote_number' => "QUOTE-{$date}-" . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        for ($i = 9004; $i <= 9008; $i++) {
            Quote::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'status' => 'sent',
                'created_at' => now(),
                'quote_number' => "QUOTE-{$date}-" . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        for ($i = 9009; $i <= 9010; $i++) {
            Quote::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'status' => 'rejected',
                'created_at' => now(),
                'quote_number' => "QUOTE-{$date}-" . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $perception = $this->agent->perceive();

        $this->assertEquals(10, $perception['quote_conversion']['total_quotes']);
        $this->assertEquals(3, $perception['quote_conversion']['converted']);
        $this->assertEquals(5, $perception['quote_conversion']['pending']);
        $this->assertEquals(2, $perception['quote_conversion']['rejected']);
        $this->assertEquals(30, $perception['quote_conversion']['conversion_rate']);
    }

    /** @test */
    public function it_only_perceives_own_organization_data(): void
    {
        $otherOrg = $this->createOtherOrganization();

        // Create invoice in other org
        Invoice::factory()->create([
            'organization_id' => $otherOrg->id,
            'status' => 'paid',
            'total_amount' => 50000,
        ]);

        // Create invoice in test org
        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'paid',
            'total_amount' => 1000,
        ]);

        $perception = $this->agent->perceive();

        // Should only see test org's sales
        $this->assertEquals(1000, $perception['sales_performance']['current_month']);
    }
}

