<?php

namespace Tests\Feature;

use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Services\Addy\ActionExecutionService;
use Tests\TestCase;

class GenerateReportActionTest extends TestCase
{
    /** @test */
    public function it_generates_a_cash_flow_report(): void
    {
        $this->authenticate();

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'is_active' => true,
        ]);

        MoneyMovement::factory()->income()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
            'created_by_id' => $this->testUser->id,
            'status' => 'approved',
            'amount' => 5000,
            'transaction_date' => now()->subDays(5),
            'category' => 'Sales Income',
            'description' => 'Invoice payment',
        ]);

        MoneyMovement::factory()->expense()->create([
            'organization_id' => $this->testOrganization->id,
            'from_account_id' => $account->id,
            'created_by_id' => $this->testUser->id,
            'status' => 'approved',
            'amount' => 1200,
            'transaction_date' => now()->subDays(3),
            'category' => 'Rent',
            'description' => 'Office rent',
        ]);

        $service = new ActionExecutionService($this->testOrganization, $this->testUser);
        $action = $service->prepareAction('generate_report', [
            'type' => 'cash_flow',
            'period' => 'last_30_days',
        ]);

        $service->confirmAction($action);
        $result = $service->executeAction($action);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('report', $result);
        $this->assertEquals('Cash Flow Report', $result['report']['title']);
        $this->assertEquals('cash_flow', $result['report']['type']);
        $this->assertEquals(5000 - 1200, $result['report']['data']['net_cash_flow']);
    }
}
