<?php

namespace Tests\Unit\Finance;

use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MoneyMovementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function income_increments_destination_account_balance(): void
    {
        $account = MoneyAccount::factory()->create([
            'current_balance' => 100,
        ]);

        $movement = MoneyMovement::factory()->create([
            'flow_type' => 'income',
            'to_account_id' => $account->id,
            'from_account_id' => null,
            'amount' => 50,
            'organization_id' => $account->organization_id,
        ]);

        $this->assertEquals(150.00, (float) $account->fresh()->current_balance);
        $this->assertEquals($account->id, $movement->to_account_id);
    }

    /** @test */
    public function expense_decrements_source_account_balance(): void
    {
        $account = MoneyAccount::factory()->create([
            'current_balance' => 200,
        ]);

        $movement = MoneyMovement::factory()->create([
            'flow_type' => 'expense',
            'from_account_id' => $account->id,
            'to_account_id' => null,
            'amount' => 75,
            'organization_id' => $account->organization_id,
        ]);

        $this->assertEquals(125.00, (float) $account->fresh()->current_balance);
        $this->assertEquals($account->id, $movement->from_account_id);
    }

    /** @test */
    public function transfer_moves_funds_between_accounts(): void
    {
        $from = MoneyAccount::factory()->create(['current_balance' => 300]);
        $to = MoneyAccount::factory()->create([
            'current_balance' => 20,
            'organization_id' => $from->organization_id,
        ]);

        MoneyMovement::factory()->create([
            'flow_type' => 'transfer',
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 80,
            'organization_id' => $from->organization_id,
        ]);

        $this->assertEquals(220.00, (float) $from->fresh()->current_balance);
        $this->assertEquals(100.00, (float) $to->fresh()->current_balance);
    }

    /** @test */
    public function dashboard_cache_is_invalidated_on_create(): void
    {
        Cache::spy();

        $account = MoneyAccount::factory()->create();

        MoneyMovement::factory()->create([
            'flow_type' => 'income',
            'to_account_id' => $account->id,
            'organization_id' => $account->organization_id,
            'amount' => 10,
        ]);

        // At least one forget call for this org should be triggered
        Cache::shouldHaveReceived('forget')
            ->withArgs(function ($key) use ($account) {
                return str_contains($key, $account->organization_id);
            })
            ->atLeast()->once();
    }
}
