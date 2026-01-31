<?php

namespace Tests\Unit\Factories;

use Tests\TestCase;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\MoneyAccount;

class PaymentFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_payment_with_default_values(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $account->id,
        ]);

        $this->assertNotEmpty($payment->payment_number);
        $this->assertNotNull($payment->amount);
        $this->assertNotNull($payment->payment_date);
        $this->assertNotEmpty($payment->payment_method);
    }

    /** @test */
    public function it_creates_payment_in_database(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $account->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'organization_id' => $this->testOrganization->id,
        ]);
    }

    /** @test */
    public function it_generates_unique_payment_numbers(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment1 = Payment::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $payment2 = Payment::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertNotEquals($payment1->payment_number, $payment2->payment_number);
    }

    /** @test */
    public function it_creates_multiple_payments(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payments = Payment::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $account->id,
        ]);

        $this->assertCount(3, $payments);
    }

    /** @test */
    public function it_creates_payment_with_custom_amount(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $account->id,
            'amount' => 5000.00,
        ]);

        $this->assertEquals(5000.00, $payment->amount);
    }

    /** @test */
    public function it_creates_payment_with_default_currency(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertEquals('ZMW', $payment->currency);
    }

    /** @test */
    public function it_creates_payment_with_cash_method_by_default(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertEquals('cash', $payment->payment_method);
    }

    /** @test */
    public function it_creates_payment_with_notes(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertNotNull($payment->notes);
    }

    /** @test */
    public function it_payment_number_matches_expected_pattern(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertMatchesRegularExpression('/PAY-[0-9]{8}-[0-9]{4}/', $payment->payment_number);
    }
}
