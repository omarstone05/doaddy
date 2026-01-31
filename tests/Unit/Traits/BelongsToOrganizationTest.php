<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Support\Str;

class BelongsToOrganizationTest extends TestCase
{
    /** @test */
    public function it_auto_assigns_organization_id_on_create(): void
    {
        $this->authenticate();

        // Simulate creating without explicitly setting organization_id
        $customer = new Customer([
            'customer_code' => 'CUS-' . Str::random(8),
            'type' => 'individual',
            'name' => 'Test Customer',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
        ]);

        $customer->save();

        $this->assertEquals($this->testOrganization->id, $customer->organization_id);
    }

    /** @test */
    public function it_does_not_override_explicit_organization_id(): void
    {
        $this->authenticate();

        $otherOrg = $this->createOtherOrganization();

        $customer = new Customer([
            'organization_id' => $otherOrg->id,
            'customer_code' => 'CUS-' . Str::random(8),
            'type' => 'individual',
            'name' => 'Other Org Customer',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
        ]);

        $customer->saveQuietly(); // Save without observers to bypass global scope

        $this->assertEquals($otherOrg->id, $customer->organization_id);
    }

    /** @test */
    public function it_scopes_queries_to_organization(): void
    {
        $this->authenticate();

        $otherOrg = $this->createOtherOrganization();

        // Create customer in test org
        Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Test Org Customer',
        ]);

        // Create customer in other org (bypass scope)
        $otherCustomer = new Customer([
            'organization_id' => $otherOrg->id,
            'customer_code' => 'CUS-' . Str::random(8),
            'type' => 'individual',
            'name' => 'Other Org Customer',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
        ]);
        $otherCustomer->saveQuietly();

        // Query should only return test org customers
        $customers = Customer::all();

        $this->assertCount(1, $customers);
        $this->assertEquals('Test Org Customer', $customers->first()->name);
    }

    /** @test */
    public function it_has_organization_relationship(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertInstanceOf(Organization::class, $customer->organization);
        $this->assertEquals($this->testOrganization->id, $customer->organization->id);
    }

    /** @test */
    public function it_works_without_authentication(): void
    {
        // Create without auth (should not auto-assign)
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertEquals($this->testOrganization->id, $customer->organization_id);
    }

    /** @test */
    public function global_scope_can_be_bypassed(): void
    {
        $this->authenticate();

        $otherOrg = $this->createOtherOrganization();

        // Create customers in different orgs
        Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $otherCustomer = new Customer([
            'organization_id' => $otherOrg->id,
            'customer_code' => 'CUS-' . Str::random(8),
            'type' => 'individual',
            'name' => 'Other Customer',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
        ]);
        $otherCustomer->saveQuietly();

        // Without scope - should see all customers
        $allCustomers = Customer::withoutGlobalScope('organization')->get();

        $this->assertCount(2, $allCustomers);
    }

    /** @test */
    public function it_works_with_model_factory(): void
    {
        // Factory should set organization_id
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertNotNull($customer->organization_id);
        $this->assertEquals($this->testOrganization->id, $customer->organization_id);
    }

    /** @test */
    public function it_works_with_nested_relationships(): void
    {
        $this->authenticate();

        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        // Both should belong to same organization
        $this->assertEquals($customer->organization_id, $invoice->organization_id);
        $this->assertEquals($this->testOrganization->id, $invoice->organization->id);
    }
}
