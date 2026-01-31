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
        $invoice1->created_at = $lastMonthDate;
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
        $invoice2->created_at = $thisMonthDate;
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
            'invoice_number' => 'INV-OTHER-001',
        ]);

        // Create invoice in test org
        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'paid',
            'total_amount' => 1000,
            'invoice_number' => 'INV-TEST-001',
        ]);

        $perception = $this->agent->perceive();

        // Should only see test org's sales
        $this->assertEquals(1000, $perception['sales_performance']['current_month']);
    }

    /** @test */
    public function it_calculates_payment_trends(): void
    {
        // Create payments this month
        Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 1000,
            'payment_date' => now()->startOfMonth()->addDays(5),
        ]);

        Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 2000,
            'payment_date' => now()->startOfMonth()->addDays(10),
        ]);

        // Create payment last month (should not be included)
        Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 5000,
            'payment_date' => now()->subMonth(),
        ]);

        $perception = $this->agent->perceive();

        $this->assertEquals(3000, $perception['payment_trends']['total_received']);
        $this->assertEquals(2, $perception['payment_trends']['payment_count']);
    }

    /** @test */
    public function it_calculates_average_days_to_payment(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Create invoice
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total_amount' => 1000,
            'created_at' => now()->subDays(30), // Invoice created 30 days ago
        ]);

        // Create payment 30 days after invoice (30 days to payment)
        $payment = Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'amount' => 1000,
            'payment_date' => now(), // Paid today
        ]);

        // Create allocation linking payment to invoice
        \App\Models\PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 1000,
        ]);

        $perception = $this->agent->perceive();

        // Should calculate average days to payment (30 days in this case)
        $this->assertEquals(30, $perception['payment_trends']['avg_days_to_payment']);
    }

    /** @test */
    public function it_returns_zero_avg_days_when_no_payments_with_allocations(): void
    {
        // Create payment without allocation
        Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 1000,
            'payment_date' => now(),
        ]);

        $perception = $this->agent->perceive();

        $this->assertEquals(0, $perception['payment_trends']['avg_days_to_payment']);
    }

    /** @test */
    public function it_calculates_average_days_for_multiple_payments(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Invoice 1: Created 20 days ago, paid today (20 days)
        $invoice1 = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total_amount' => 1000,
            'created_at' => now()->subDays(20),
        ]);

        $payment1 = Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'amount' => 1000,
            'payment_date' => now(),
        ]);

        \App\Models\PaymentAllocation::create([
            'payment_id' => $payment1->id,
            'invoice_id' => $invoice1->id,
            'amount' => 1000,
        ]);

        // Invoice 2: Created 40 days ago, paid today (40 days)
        $invoice2 = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total_amount' => 2000,
            'created_at' => now()->subDays(40),
        ]);

        $payment2 = Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'amount' => 2000,
            'payment_date' => now(),
        ]);

        \App\Models\PaymentAllocation::create([
            'payment_id' => $payment2->id,
            'invoice_id' => $invoice2->id,
            'amount' => 2000,
        ]);

        $perception = $this->agent->perceive();

        // Average should be (20 + 40) / 2 = 30 days
        $this->assertEquals(30, $perception['payment_trends']['avg_days_to_payment']);
    }

    /** @test */
    public function it_generates_overdue_invoice_alert(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $date = now()->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'due_date' => now()->subDays(5),
            'total_amount' => 5000,
            'invoice_number' => "INV-{$date}-0001",
        ]);

        $insights = $this->agent->analyze();

        $overdueInsight = collect($insights)->firstWhere('title', 'Overdue Invoices Detected');
        
        $this->assertNotNull($overdueInsight);
        $this->assertEquals('alert', $overdueInsight['type']);
        $this->assertEquals('sales', $overdueInsight['category']);
        $this->assertGreaterThan(0.8, $overdueInsight['priority']);
        $this->assertTrue($overdueInsight['is_actionable']);
        $this->assertContains('Send payment reminders to customers', $overdueInsight['suggested_actions']);
    }

    /** @test */
    public function it_generates_pending_invoice_observation(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $date = now()->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'due_date' => now()->addDays(5),
            'total_amount' => 3000,
            'invoice_number' => "INV-{$date}-0001",
        ]);

        $insights = $this->agent->analyze();

        $pendingInsight = collect($insights)->firstWhere('title', 'Outstanding Invoices');
        
        $this->assertNotNull($pendingInsight);
        $this->assertEquals('observation', $pendingInsight['type']);
        $this->assertEquals('sales', $pendingInsight['category']);
        $this->assertTrue($pendingInsight['is_actionable']);
    }

    /** @test */
    public function it_generates_sales_decline_alert(): void
    {
        $uniqueOrg = $this->createOtherOrganization();
        $uniqueAgent = new SalesAgent($uniqueOrg);
        
        $customer = Customer::factory()->create([
            'organization_id' => $uniqueOrg->id,
        ]);

        // Last month: $10000
        $lastMonthDate = now()->subMonth()->startOfMonth()->addDays(15);
        $lastMonthDateStr = $lastMonthDate->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $uniqueOrg->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 10000,
            'invoice_date' => $lastMonthDate,
            'created_at' => $lastMonthDate,
            'invoice_number' => "INV-{$lastMonthDateStr}-9999",
        ]);

        // This month: $8000 (20% decrease - triggers decline alert)
        $thisMonthDate = now()->startOfMonth()->addDays(15);
        $thisMonthDateStr = $thisMonthDate->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $uniqueOrg->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 8000,
            'invoice_date' => $thisMonthDate,
            'created_at' => $thisMonthDate,
            'invoice_number' => "INV-{$thisMonthDateStr}-9999",
        ]);

        $insights = $uniqueAgent->analyze();

        $declineInsight = collect($insights)->firstWhere('title', 'Sales Decline Detected');
        
        $this->assertNotNull($declineInsight);
        $this->assertEquals('alert', $declineInsight['type']);
        $this->assertGreaterThan(0.7, $declineInsight['priority']);
        $this->assertTrue($declineInsight['is_actionable']);
    }

    /** @test */
    public function it_generates_sales_growth_achievement(): void
    {
        $uniqueOrg = $this->createOtherOrganization();
        $uniqueAgent = new SalesAgent($uniqueOrg);
        
        $customer = Customer::factory()->create([
            'organization_id' => $uniqueOrg->id,
        ]);

        // Last month: $5000
        $lastMonthDate = now()->subMonth()->startOfMonth()->addDays(15);
        $lastMonthDateStr = $lastMonthDate->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $uniqueOrg->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 5000,
            'invoice_date' => $lastMonthDate,
            'created_at' => $lastMonthDate,
            'invoice_number' => "INV-{$lastMonthDateStr}-9999",
        ]);

        // This month: $7000 (40% increase - triggers growth achievement)
        $thisMonthDate = now()->startOfMonth()->addDays(15);
        $thisMonthDateStr = $thisMonthDate->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $uniqueOrg->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 7000,
            'invoice_date' => $thisMonthDate,
            'created_at' => $thisMonthDate,
            'invoice_number' => "INV-{$thisMonthDateStr}-9999",
        ]);

        $insights = $uniqueAgent->analyze();

        $growthInsight = collect($insights)->firstWhere('title', 'Strong Sales Growth!');
        
        $this->assertNotNull($growthInsight);
        $this->assertEquals('achievement', $growthInsight['type']);
        $this->assertFalse($growthInsight['is_actionable']); // Achievements are not actionable
    }

    /** @test */
    public function it_generates_low_quote_conversion_suggestion(): void
    {
        // Clear existing quotes
        Quote::where('organization_id', $this->testOrganization->id)->delete();
        
        $date = now()->format('Ymd');
        
        // Create 10 quotes with low conversion (only 2 accepted = 20%)
        for ($i = 8001; $i <= 8002; $i++) {
            Quote::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'status' => 'accepted',
                'created_at' => now(),
                'quote_number' => "QUOTE-{$date}-" . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        for ($i = 8003; $i <= 8010; $i++) {
            Quote::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'status' => 'rejected',
                'created_at' => now(),
                'quote_number' => "QUOTE-{$date}-" . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $insights = $this->agent->analyze();

        $conversionInsight = collect($insights)->firstWhere('title', 'Low Quote Conversion Rate');
        
        $this->assertNotNull($conversionInsight);
        $this->assertEquals('suggestion', $conversionInsight['type']);
        $this->assertTrue($conversionInsight['is_actionable']);
        $this->assertContains('Review pricing strategy', $conversionInsight['suggested_actions']);
    }

    /** @test */
    public function it_generates_new_customer_growth_achievement(): void
    {
        // Create 6 new customers this month
        Customer::factory()->count(6)->create([
            'organization_id' => $this->testOrganization->id,
            'created_at' => now()->startOfMonth()->addDays(5),
        ]);

        $insights = $this->agent->analyze();

        $customerInsight = collect($insights)->firstWhere('title', 'New Customer Growth');
        
        $this->assertNotNull($customerInsight);
        $this->assertEquals('achievement', $customerInsight['type']);
        $this->assertFalse($customerInsight['is_actionable']);
    }

    /** @test */
    public function it_generates_slow_payment_collection_suggestion(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Create invoice 50 days ago
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total_amount' => 1000,
            'created_at' => now()->subDays(50),
        ]);

        // Create payment today (50 days to payment)
        $payment = Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'amount' => 1000,
            'payment_date' => now(),
        ]);

        \App\Models\PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 1000,
        ]);

        $insights = $this->agent->analyze();

        $paymentInsight = collect($insights)->firstWhere('title', 'Slow Payment Collection');
        
        $this->assertNotNull($paymentInsight);
        $this->assertEquals('suggestion', $paymentInsight['type']);
        $this->assertTrue($paymentInsight['is_actionable']);
        $this->assertContains('Review payment terms', $paymentInsight['suggested_actions']);
    }

    /** @test */
    public function it_handles_stable_sales_trend(): void
    {
        $uniqueOrg = $this->createOtherOrganization();
        $uniqueAgent = new SalesAgent($uniqueOrg);
        
        $customer = Customer::factory()->create([
            'organization_id' => $uniqueOrg->id,
        ]);

        // Last month: $5000
        $lastMonthDate = now()->subMonth()->startOfMonth()->addDays(15);
        $lastMonthDateStr = $lastMonthDate->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $uniqueOrg->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 5000,
            'invoice_date' => $lastMonthDate,
            'created_at' => $lastMonthDate,
            'invoice_number' => "INV-{$lastMonthDateStr}-9999",
        ]);

        // This month: $5100 (2% increase - stable trend)
        $thisMonthDate = now()->startOfMonth()->addDays(15);
        $thisMonthDateStr = $thisMonthDate->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $uniqueOrg->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 5100,
            'invoice_date' => $thisMonthDate,
            'created_at' => $thisMonthDate,
            'invoice_number' => "INV-{$thisMonthDateStr}-9999",
        ]);

        $perception = $uniqueAgent->perceive();

        $this->assertEquals('stable', $perception['sales_performance']['trend']);
    }

    /** @test */
    public function it_handles_zero_sales_last_month(): void
    {
        $uniqueOrg = $this->createOtherOrganization();
        $uniqueAgent = new SalesAgent($uniqueOrg);
        
        $customer = Customer::factory()->create([
            'organization_id' => $uniqueOrg->id,
        ]);

        // This month: $5000 (no last month sales)
        $thisMonthDate = now()->startOfMonth()->addDays(15);
        $thisMonthDateStr = $thisMonthDate->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $uniqueOrg->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 5000,
            'invoice_date' => $thisMonthDate,
            'created_at' => $thisMonthDate,
            'invoice_number' => "INV-{$thisMonthDateStr}-9999",
        ]);

        $perception = $uniqueAgent->perceive();

        $this->assertEquals(5000, $perception['sales_performance']['current_month']);
        $this->assertEquals(0, $perception['sales_performance']['last_month']);
        $this->assertEquals(0, $perception['sales_performance']['change_percentage']);
    }

    /** @test */
    public function it_handles_empty_data_gracefully(): void
    {
        // No data created - test with empty organization
        $perception = $this->agent->perceive();

        $this->assertEquals(0, $perception['customer_stats']['total']);
        $this->assertEquals(0, $perception['invoice_health']['overdue_count']);
        $this->assertEquals(0, $perception['sales_performance']['current_month']);
        $this->assertEquals(0, $perception['quote_conversion']['total_quotes']);
        $this->assertEquals(0, $perception['payment_trends']['total_received']);

        $insights = $this->agent->analyze();
        
        // Should return empty array when no insights are generated
        $this->assertIsArray($insights);
    }

    /** @test */
    public function it_only_analyzes_own_organization_data(): void
    {
        $otherOrg = $this->createOtherOrganization();

        // Create overdue invoice in other org
        $otherCustomer = Customer::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        $date = now()->format('Ymd');
        Invoice::factory()->create([
            'organization_id' => $otherOrg->id,
            'customer_id' => $otherCustomer->id,
            'status' => 'sent',
            'due_date' => now()->subDays(5),
            'total_amount' => 10000,
            'invoice_number' => "INV-{$date}-9999",
        ]);

        $insights = $this->agent->analyze();

        // Should not generate overdue alert for other org's invoice
        $overdueInsight = collect($insights)->firstWhere('title', 'Overdue Invoices Detected');
        $this->assertNull($overdueInsight);
    }

    /** @test */
    public function it_caches_perception_data(): void
    {
        // Create initial data
        Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $perception1 = $this->agent->perceive();
        $customerCount1 = $perception1['customer_stats']['total'];

        // Add more customers
        Customer::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Should return cached data (same customer count)
        $perception2 = $this->agent->perceive();
        $customerCount2 = $perception2['customer_stats']['total'];

        $this->assertEquals($customerCount1, $customerCount2);
    }
}

