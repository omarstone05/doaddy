<?php

namespace Tests\Unit\Finance;

use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeExpenseCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_income_and_expense_movements_and_updates_balances(): void
    {
        $account = MoneyAccount::factory()->create(['current_balance' => 100]);

        $income = MoneyMovement::factory()->create([
            'flow_type' => 'income',
            'to_account_id' => $account->id,
            'from_account_id' => null,
            'amount' => 60,
            'organization_id' => $account->organization_id,
        ]);

        $this->assertEquals(160.00, (float) $account->fresh()->current_balance);
        $this->assertEquals('income', $income->flow_type);

        $expense = MoneyMovement::factory()->create([
            'flow_type' => 'expense',
            'from_account_id' => $account->id,
            'to_account_id' => null,
            'amount' => 40,
            'organization_id' => $account->organization_id,
        ]);

        $this->assertEquals(120.00, (float) $account->fresh()->current_balance);
        $this->assertEquals('expense', $expense->flow_type);
    }

    /** @test */
    public function it_updates_and_deletes_transactions(): void
    {
        $account = MoneyAccount::factory()->create(['current_balance' => 200]);

        $movement = MoneyMovement::factory()->create([
            'flow_type' => 'income',
            'to_account_id' => $account->id,
            'amount' => 50,
            'organization_id' => $account->organization_id,
        ]);

        $movement->update(['description' => 'Updated desc']);
        $this->assertEquals('Updated desc', $movement->fresh()->description);

        $movementId = $movement->id;
        $movement->delete();
        $this->assertDatabaseMissing('money_movements', ['id' => $movementId]);
    }
}
