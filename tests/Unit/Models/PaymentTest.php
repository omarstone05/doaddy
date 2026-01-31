<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Models\Receipt;
use Illuminate\Support\Str;

class PaymentTest extends TestCase
{
    /** @test */
    public function it_generates_payment_number_automatically(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertNotNull($payment->payment_number);
        $this->assertStringStartsWith('PAY-', $payment->payment_number);
    }

    /** @test */
    public function it_generates_unique_payment_numbers_for_same_day(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment1 = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        $payment2 = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 2000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        $this->assertNotEquals($payment1->payment_number, $payment2->payment_number);
    }

    /** @test */
    public function it_creates_money_movement_automatically(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 0,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
        ]);

        $movement = MoneyMovement::where('related_id', $payment->id)
            ->where('related_type', 'Payment')
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals('income', $movement->flow_type);
        $this->assertEquals(1000, $movement->amount);
        $this->assertEquals($moneyAccount->id, $movement->to_account_id);
    }

    /** @test */
    public function it_creates_receipt_automatically(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        $receipt = Receipt::where('payment_id', $payment->id)->first();

        $this->assertNotNull($receipt);
        $this->assertStringStartsWith('RCP-', $receipt->receipt_number);
    }

    /** @test */
    public function it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        $this->assertInstanceOf(Customer::class, $payment->customer);
        $this->assertEquals($customer->id, $payment->customer->id);
    }

    /** @test */
    public function it_belongs_to_money_account(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Business Account',
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertInstanceOf(MoneyAccount::class, $payment->moneyAccount);
        $this->assertEquals('Business Account', $payment->moneyAccount->name);
    }

    /** @test */
    public function it_calculates_allocated_amount_correctly(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'total_amount' => 600,
        ]);

        PaymentAllocation::create([
            'id' => (string) Str::uuid(),
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 600,
        ]);

        $this->assertEquals(600, $payment->allocated_amount);
        $this->assertEquals(400, $payment->unallocated_amount);
    }

    /** @test */
    public function it_has_many_allocations(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        $invoice1 = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $invoice2 = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        PaymentAllocation::create([
            'id' => (string) Str::uuid(),
            'payment_id' => $payment->id,
            'invoice_id' => $invoice1->id,
            'amount' => 400,
        ]);

        PaymentAllocation::create([
            'id' => (string) Str::uuid(),
            'payment_id' => $payment->id,
            'invoice_id' => $invoice2->id,
            'amount' => 300,
        ]);

        $this->assertCount(2, $payment->allocations);
    }

    /** @test */
    public function it_has_many_receipts(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        // One receipt is auto-created, payment should have at least one
        $this->assertGreaterThanOrEqual(1, $payment->receipts->count());
    }

    /** @test */
    public function it_casts_amount_as_decimal(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1234.56,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertEquals('1234.56', $payment->amount);
    }

    /** @test */
    public function it_casts_date_correctly(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => '2024-06-15',
            'payment_method' => 'cash',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $payment->payment_date);
    }

    /** @test */
    public function it_handles_different_payment_methods(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $methods = ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'card'];

        foreach ($methods as $method) {
            $payment = Payment::create([
                'organization_id' => $this->testOrganization->id,
                'customer_id' => $customer->id,
                'money_account_id' => $moneyAccount->id,
                'amount' => 100,
                'currency' => 'ZMW',
                'payment_date' => now(),
                'payment_method' => $method,
            ]);

            $this->assertEquals($method, $payment->payment_method);
        }
    }

    /** @test */
    public function it_respects_organization_isolation(): void
    {
        $otherOrg = $this->createOtherOrganization();

        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $otherCustomer = Customer::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $otherMoneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        Payment::create([
            'organization_id' => $otherOrg->id,
            'customer_id' => $otherCustomer->id,
            'money_account_id' => $otherMoneyAccount->id,
            'amount' => 2000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        $testOrgPayments = Payment::where('organization_id', $this->testOrganization->id)->get();
        $otherOrgPayments = Payment::where('organization_id', $otherOrg->id)->get();

        $this->assertCount(1, $testOrgPayments);
        $this->assertCount(1, $otherOrgPayments);
        $this->assertEquals(1000, $testOrgPayments->first()->amount);
    }

    /** @test */
    public function it_stores_payment_reference(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'TXN-12345-XYZ',
        ]);

        $this->assertEquals('TXN-12345-XYZ', $payment->payment_reference);
    }

    /** @test */
    public function it_stores_notes(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $moneyAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $moneyAccount->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
            'notes' => 'Partial payment for Invoice #123',
        ]);

        $this->assertEquals('Partial payment for Invoice #123', $payment->notes);
    }
}
