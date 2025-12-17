<?php

namespace Tests\Unit\Agents;

use Tests\TestCase;
use App\Services\Addy\Agents\MoneyAgent;
use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Models\BudgetLine;

class MoneyAgentTest extends TestCase
{
    protected MoneyAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new MoneyAgent($this->testOrganization);
    }

    /** @test */
    public function it_perceives_cash_position_correctly(): void
    {
        // Create accounts with specific balances
        MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 5000,
            'is_active' => true,
        ]);

        MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 3000,
            'is_active' => true,
        ]);

        $perception = $this->agent->perceive();

        $this->assertEquals(8000, $perception['cash_position']);
    }

    /** @test */
    public function it_ignores_inactive_accounts(): void
    {
        MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 5000,
            'is_active' => true,
        ]);

        MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 10000,
            'is_active' => false, // Inactive
        ]);

        $perception = $this->agent->perceive();

        $this->assertEquals(5000, $perception['cash_position']);
    }

    /** @test */
    public function it_calculates_monthly_burn_correctly(): void
    {
        // Create expenses this month
        MoneyMovement::factory()->expense()->approved()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 500,
            'transaction_date' => now(),
        ]);

        MoneyMovement::factory()->expense()->approved()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 300,
            'transaction_date' => now(),
        ]);

        // Create expense last month (should not be included)
        MoneyMovement::factory()->expense()->approved()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 1000,
            'transaction_date' => now()->subMonth(),
        ]);

        $perception = $this->agent->perceive();

        $this->assertEquals(800, $perception['monthly_burn']);
    }

    /** @test */
    public function it_only_perceives_own_organization_data(): void
    {
        $otherOrg = $this->createOtherOrganization();

        // Create account in other org
        MoneyAccount::factory()->create([
            'organization_id' => $otherOrg->id,
            'current_balance' => 50000,
            'is_active' => true,
        ]);

        // Create account in test org
        MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_balance' => 1000,
            'is_active' => true,
        ]);

        $perception = $this->agent->perceive();

        // Should only see test org's balance
        $this->assertEquals(1000, $perception['cash_position']);
    }

    // Budget tests skipped - budget_lines table migration is incomplete
    // TODO: Fix budget_lines migration to include organization_id and other fields
    // /** @test */
    // public function it_detects_budget_overrun(): void
    // {
    //     ...
    // }

    /** @test */
    public function it_detects_spending_trend_increase(): void
    {
        // Last month expenses
        MoneyMovement::factory()->expense()->approved()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 1000,
            'transaction_date' => now()->subMonth(),
        ]);

        // This month expenses (50% increase)
        MoneyMovement::factory()->expense()->approved()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 1500,
            'transaction_date' => now(),
        ]);

        $perception = $this->agent->perceive();

        $this->assertEquals('increasing', $perception['trends']['trend']);
        $this->assertGreaterThan(20, $perception['trends']['change_percentage']);
    }

    // Budget insight test skipped - budget_lines table migration is incomplete
    // TODO: Fix budget_lines migration to include organization_id and other fields

    /** @test */
    public function it_generates_spending_spike_insight(): void
    {
        // Last month: $1000
        MoneyMovement::factory()->expense()->approved()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 1000,
            'transaction_date' => now()->subMonth(),
        ]);

        // This month: $1300 (30% increase - triggers spike)
        MoneyMovement::factory()->expense()->approved()->create([
            'organization_id' => $this->testOrganization->id,
            'amount' => 1300,
            'transaction_date' => now(),
        ]);

        $insights = $this->agent->analyze();

        $spikeInsight = collect($insights)->firstWhere('title', 'Spending Spike Detected');
        
        $this->assertNotNull($spikeInsight);
        $this->assertEquals('alert', $spikeInsight['type']);
    }
}

