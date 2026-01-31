<?php

namespace Tests\Unit\Finance;

use App\Models\MoneyAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyAccountCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_reads_updates_and_soft_deletes_money_account(): void
    {
        $account = MoneyAccount::factory()->create([
            'name' => 'Main Cash',
            'type' => 'cash',
            'current_balance' => 500,
        ]);

        $this->assertDatabaseHas('money_accounts', [
            'id' => $account->id,
            'name' => 'Main Cash',
        ]);

        $account->update(['name' => 'Updated Cash']);
        $this->assertEquals('Updated Cash', $account->fresh()->name);

        $account->delete();
        $this->assertDatabaseMissing('money_accounts', ['id' => $account->id]);
    }
}
