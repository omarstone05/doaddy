<?php

namespace Tests\Unit\Factories;

use Tests\TestCase;
use App\Models\Customer;

class CustomerFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_customer_with_default_values(): void
    {
        $customer = Customer::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertNotEmpty($customer->customer_code);
        $this->assertNotEmpty($customer->name);
        $this->assertNotEmpty($customer->email);
        $this->assertEquals('business', $customer->type);
    }

    /** @test */
    public function it_creates_customer_in_database(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'organization_id' => $this->testOrganization->id,
        ]);
    }

    /** @test */
    public function it_generates_unique_customer_codes(): void
    {
        $customer1 = Customer::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        $customer2 = Customer::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertNotEquals($customer1->customer_code, $customer2->customer_code);
    }

    /** @test */
    public function it_generates_unique_emails(): void
    {
        $customer1 = Customer::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        $customer2 = Customer::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertNotEquals($customer1->email, $customer2->email);
    }

    /** @test */
    public function it_creates_multiple_customers(): void
    {
        $customers = Customer::factory()->count(5)->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertCount(5, $customers);
    }

    /** @test */
    public function it_creates_customer_with_optional_fields(): void
    {
        $customer = Customer::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'billing_address' => '123 Test Street',
            'shipping_address' => '456 Ship Lane',
            'tax_id' => 'TAX-1234',
        ]);

        $this->assertEquals('123 Test Street', $customer->billing_address);
        $this->assertEquals('456 Ship Lane', $customer->shipping_address);
        $this->assertEquals('TAX-1234', $customer->tax_id);
    }

    /** @test */
    public function it_creates_customer_with_phone_number(): void
    {
        $customer = Customer::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertNotEmpty($customer->phone);
    }

    /** @test */
    public function it_associates_customer_with_organization(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertEquals($this->testOrganization->id, $customer->organization_id);
        $this->assertEquals($this->testOrganization->id, $customer->organization->id);
    }

    /** @test */
    public function it_customer_code_starts_with_cus_prefix(): void
    {
        $customer = Customer::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertStringStartsWith('CUS-', $customer->customer_code);
    }
}
