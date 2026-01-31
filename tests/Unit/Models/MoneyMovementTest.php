<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\MoneyMovement;
use App\Models\MoneyAccount;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class MoneyMovementTest extends TestCase
{
    /** @test */
    public function it_belongs_to_organization(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $movement = MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
        ]);

        $this->assertEquals($this->testOrganization->id, $movement->organization_id);
    }

    /** @test */
    public function it_increments_account_balance_on_income(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 1000,
            'is_active' => true,
        ]);

        MoneyMovement::create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'income',
            'amount' => 500,
            'currency' => 'ZMW',
            'transaction_date' => now(),
            'to_account_id' => $account->id,
            'description' => 'Test income',
            'status' => 'approved',
        ]);

        $account->refresh();
        $this->assertEquals(1500, $account->current_balance);
    }

    /** @test */
    public function it_decrements_account_balance_on_expense(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 1000,
            'is_active' => true,
        ]);

        MoneyMovement::create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'expense',
            'amount' => 300,
            'currency' => 'ZMW',
            'transaction_date' => now(),
            'from_account_id' => $account->id,
            'description' => 'Test expense',
            'status' => 'approved',
        ]);

        $account->refresh();
        $this->assertEquals(700, $account->current_balance);
    }

    /** @test */
    public function it_handles_transfer_between_accounts(): void
    {
        $fromAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 1000,
            'is_active' => true,
            'name' => 'Source Account',
        ]);

        $toAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 500,
            'is_active' => true,
            'name' => 'Destination Account',
        ]);

        MoneyMovement::create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'transfer',
            'amount' => 400,
            'currency' => 'ZMW',
            'transaction_date' => now(),
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'description' => 'Transfer between accounts',
            'status' => 'approved',
        ]);

        $fromAccount->refresh();
        $toAccount->refresh();

        $this->assertEquals(600, $fromAccount->current_balance);
        $this->assertEquals(900, $toAccount->current_balance);
    }

    /** @test */
    public function it_belongs_to_from_account(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Main Account',
        ]);

        $movement = MoneyMovement::factory()->expense()->create([
            'organization_id' => $this->testOrganization->id,
            'from_account_id' => $account->id,
        ]);

        $this->assertInstanceOf(MoneyAccount::class, $movement->fromAccount);
        $this->assertEquals('Main Account', $movement->fromAccount->name);
    }

    /** @test */
    public function it_belongs_to_to_account(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Receiving Account',
        ]);

        $movement = MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
            'flow_type' => 'income',
        ]);

        $this->assertInstanceOf(MoneyAccount::class, $movement->toAccount);
        $this->assertEquals('Receiving Account', $movement->toAccount->name);
    }

    /** @test */
    public function it_belongs_to_created_by_user(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $movement = MoneyMovement::create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'income',
            'amount' => 100,
            'currency' => 'ZMW',
            'transaction_date' => now(),
            'to_account_id' => $account->id,
            'description' => 'Test',
            'status' => 'approved',
            'created_by_id' => $this->testUser->id,
        ]);

        $this->assertInstanceOf(User::class, $movement->createdBy);
        $this->assertEquals($this->testUser->id, $movement->createdBy->id);
    }

    /** @test */
    public function it_casts_amount_as_decimal(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $movement = MoneyMovement::create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'income',
            'amount' => 1234.56,
            'currency' => 'ZMW',
            'transaction_date' => now(),
            'to_account_id' => $account->id,
            'description' => 'Test',
            'status' => 'approved',
        ]);

        $this->assertEquals('1234.56', $movement->amount);
    }

    /** @test */
    public function it_casts_transaction_date_correctly(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $movement = MoneyMovement::create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'income',
            'amount' => 100,
            'currency' => 'ZMW',
            'transaction_date' => '2024-06-15',
            'to_account_id' => $account->id,
            'description' => 'Test',
            'status' => 'approved',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $movement->transaction_date);
    }

    /** @test */
    public function it_handles_verification_fields(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $movement = MoneyMovement::create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'income',
            'amount' => 100,
            'currency' => 'ZMW',
            'transaction_date' => now(),
            'to_account_id' => $account->id,
            'description' => 'Test',
            'status' => 'approved',
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by_id' => $this->testUser->id,
        ]);

        $this->assertTrue($movement->is_verified);
        $this->assertInstanceOf(\Carbon\Carbon::class, $movement->verified_at);
    }

    /** @test */
    public function it_handles_related_polymorphic_fields(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $movement = MoneyMovement::create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'income',
            'amount' => 100,
            'currency' => 'ZMW',
            'transaction_date' => now(),
            'to_account_id' => $account->id,
            'description' => 'Test',
            'status' => 'approved',
            'related_type' => 'Payment',
            'related_id' => 'some-uuid-here',
        ]);

        $this->assertEquals('Payment', $movement->related_type);
        $this->assertEquals('some-uuid-here', $movement->related_id);
    }

    /** @test */
    public function it_handles_category_field(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $movement = MoneyMovement::create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'expense',
            'amount' => 100,
            'currency' => 'ZMW',
            'transaction_date' => now(),
            'from_account_id' => $account->id,
            'description' => 'Office supplies',
            'category' => 'office_supplies',
            'status' => 'approved',
        ]);

        $this->assertEquals('office_supplies', $movement->category);
    }

    /** @test */
    public function it_handles_different_statuses(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $pending = MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
            'status' => 'pending',
        ]);

        $approved = MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
            'status' => 'approved',
        ]);

        $rejected = MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
            'status' => 'rejected',
        ]);

        $this->assertEquals('pending', $pending->status);
        $this->assertEquals('approved', $approved->status);
        $this->assertEquals('rejected', $rejected->status);
    }

    /** @test */
    public function it_respects_organization_isolation(): void
    {
        $otherOrg = $this->createOtherOrganization();

        $testAccount = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $otherAccount = MoneyAccount::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $testAccount->id,
            'amount' => 100,
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $otherOrg->id,
            'to_account_id' => $otherAccount->id,
            'amount' => 200,
        ]);

        $testOrgMovements = MoneyMovement::where('organization_id', $this->testOrganization->id)->get();
        $otherOrgMovements = MoneyMovement::where('organization_id', $otherOrg->id)->get();

        $this->assertCount(1, $testOrgMovements);
        $this->assertCount(1, $otherOrgMovements);
        $this->assertEquals(100, $testOrgMovements->first()->amount);
    }

    /** @test */
    public function it_can_filter_by_flow_type(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'income',
            'to_account_id' => $account->id,
        ]);

        MoneyMovement::factory()->expense()->create([
            'organization_id' => $this->testOrganization->id,
            'from_account_id' => $account->id,
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'income',
            'to_account_id' => $account->id,
        ]);

        $incomeMovements = MoneyMovement::where('organization_id', $this->testOrganization->id)
            ->where('flow_type', 'income')
            ->get();

        $expenseMovements = MoneyMovement::where('organization_id', $this->testOrganization->id)
            ->where('flow_type', 'expense')
            ->get();

        $this->assertCount(2, $incomeMovements);
        $this->assertCount(1, $expenseMovements);
    }

    /** @test */
    public function it_can_filter_by_date_range(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
            'transaction_date' => now()->subDays(10),
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
            'transaction_date' => now()->addDays(5),
        ]);

        $thisWeek = MoneyMovement::where('organization_id', $this->testOrganization->id)
            ->whereBetween('transaction_date', [now()->subDays(7), now()])
            ->get();

        $this->assertCount(1, $thisWeek);
    }
}
