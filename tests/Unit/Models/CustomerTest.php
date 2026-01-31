<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\CustomerPersona;
use App\Models\Invoice;
use App\Models\Quotation;

class CustomerTest extends TestCase
{
    /** @test */
    public function it_generates_customer_code_automatically(): void
    {
        $customer = Customer::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'business',
            'name' => 'Test Customer',
            'email' => 'test@customer.com',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
        ]);

        $this->assertNotNull($customer->customer_code);
        $this->assertStringStartsWith('CUS', $customer->customer_code);
    }

    /** @test */
    public function it_defaults_credit_limit_to_zero(): void
    {
        $customer = Customer::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'individual',
            'name' => 'Test Customer',
            'payment_terms' => 'immediate',
            'currency' => 'ZMW',
        ]);

        $this->assertEquals(0, $customer->credit_limit);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertEquals($this->testOrganization->id, $customer->organization->id);
    }

    /** @test */
    public function it_can_belong_to_persona(): void
    {
        $persona = CustomerPersona::create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'VIP Customer',
            'size' => 'large',
            'payment_behavior' => 'excellent',
            'color' => '#FFD700',
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_persona_id' => $persona->id,
        ]);

        $this->assertInstanceOf(CustomerPersona::class, $customer->persona);
        $this->assertEquals('VIP Customer', $customer->persona->name);
    }

    /** @test */
    public function it_has_many_invoices(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        Invoice::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertCount(3, $customer->invoices);
    }

    /** @test */
    public function it_calculates_payment_terms_days_correctly(): void
    {
        $immediateCustomer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'payment_terms' => 'immediate',
        ]);

        $net15Customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'payment_terms' => 'net_15',
        ]);

        $net30Customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'payment_terms' => 'net_30',
        ]);

        $net60Customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'payment_terms' => 'net_60',
        ]);

        $net90Customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'payment_terms' => 'net_90',
        ]);

        $customCustomer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'payment_terms' => 'custom',
            'custom_payment_days' => 45,
        ]);

        $this->assertEquals(0, $immediateCustomer->payment_terms_days);
        $this->assertEquals(15, $net15Customer->payment_terms_days);
        $this->assertEquals(30, $net30Customer->payment_terms_days);
        $this->assertEquals(60, $net60Customer->payment_terms_days);
        $this->assertEquals(90, $net90Customer->payment_terms_days);
        $this->assertEquals(45, $customCustomer->payment_terms_days);
    }

    /** @test */
    public function it_scopes_active_customers(): void
    {
        Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
        ]);

        Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'inactive',
        ]);

        Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'blocked',
        ]);

        $activeCustomers = Customer::where('organization_id', $this->testOrganization->id)
            ->active()
            ->get();

        $this->assertCount(1, $activeCustomers);
    }

    /** @test */
    public function it_scopes_customers_with_outstanding_balance(): void
    {
        Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'outstanding_balance' => 500,
        ]);

        Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'outstanding_balance' => 0,
        ]);

        $customersWithBalance = Customer::where('organization_id', $this->testOrganization->id)
            ->withOutstandingBalance()
            ->get();

        $this->assertCount(1, $customersWithBalance);
        $this->assertEquals(500, $customersWithBalance->first()->outstanding_balance);
    }

    /** @test */
    public function it_updates_financial_metrics(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'lifetime_value' => 0,
            'outstanding_balance' => 0,
            'total_orders' => 0,
        ]);

        // Create a paid invoice
        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 1000,
        ]);

        // Create an unpaid invoice
        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total_amount' => 500,
            'paid_amount' => 0,
        ]);

        $customer->updateFinancialMetrics();

        $this->assertEquals(2, $customer->total_orders);
    }

    /** @test */
    public function it_casts_arrays_correctly(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'tags' => ['vip', 'priority'],
            'custom_fields' => ['industry' => 'technology', 'size' => 'enterprise'],
        ]);

        $this->assertIsArray($customer->tags);
        $this->assertIsArray($customer->custom_fields);
        $this->assertContains('vip', $customer->tags);
        $this->assertEquals('technology', $customer->custom_fields['industry']);
    }

    /** @test */
    public function it_respects_organization_isolation(): void
    {
        $otherOrg = $this->createOtherOrganization();

        Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Test Org Customer',
        ]);

        Customer::factory()->create([
            'organization_id' => $otherOrg->id,
            'name' => 'Other Org Customer',
        ]);

        $testOrgCustomers = Customer::where('organization_id', $this->testOrganization->id)->get();

        $this->assertCount(1, $testOrgCustomers);
        $this->assertEquals('Test Org Customer', $testOrgCustomers->first()->name);
    }

    /** @test */
    public function it_soft_deletes_customers(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $customerId = $customer->id;
        $customer->delete();

        // Customer should not appear in normal queries
        $this->assertNull(Customer::find($customerId));

        // Customer should appear when including trashed
        $this->assertNotNull(Customer::withTrashed()->find($customerId));
    }

    /** @test */
    public function it_handles_primary_contact_fields(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'business',
            'primary_contact_name' => 'John Doe',
            'primary_contact_email' => 'john@company.com',
            'primary_contact_phone' => '+260123456789',
        ]);

        $this->assertEquals('John Doe', $customer->primary_contact_name);
        $this->assertEquals('john@company.com', $customer->primary_contact_email);
        $this->assertEquals('+260123456789', $customer->primary_contact_phone);
    }

    /** @test */
    public function it_casts_dates_correctly(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'first_purchase_date' => '2024-01-15',
            'last_purchase_date' => '2024-06-20',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $customer->first_purchase_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $customer->last_purchase_date);
    }

    /** @test */
    public function it_handles_different_customer_types(): void
    {
        $individual = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'individual',
        ]);

        $business = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'business',
        ]);

        $this->assertEquals('individual', $individual->type);
        $this->assertEquals('business', $business->type);
    }
}
