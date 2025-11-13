<?php

namespace Tests\Feature;

use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Services\Addy\ActionExecutionService;
use Tests\TestCase;

class CategorizeTransactionsActionTest extends TestCase
{
    /** @test */
    public function it_categorizes_uncategorized_expenses(): void
    {
        $this->authenticate();

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'is_active' => true,
        ]);

        $movement = MoneyMovement::factory()
            ->expense()
            ->create([
                'organization_id' => $this->testOrganization->id,
                'from_account_id' => $account->id,
                'created_by_id' => $this->testUser->id,
                'status' => 'approved',
                'category' => null,
                'description' => 'Coffee meeting with client',
                'amount' => 42.50,
                'transaction_date' => now(),
            ]);

        $service = new ActionExecutionService($this->testOrganization, $this->testUser);

        $action = $service->prepareAction('categorize_transactions');
        $service->confirmAction($action);
        $result = $service->executeAction($action);

        $movement->refresh();

        $this->assertTrue($result['success']);
        $this->assertEquals('Meals & Entertainment', $movement->category);
    }
}
