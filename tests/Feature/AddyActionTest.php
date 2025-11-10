<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AddyAction;
use App\Services\Addy\ActionExecutionService;
use App\Models\MoneyAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddyActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a money account for transaction actions
        MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_confirm_action(): void
    {
        $this->authenticate();

        // Create action using ActionExecutionService to ensure proper setup
        $service = new ActionExecutionService($this->testOrganization, $this->testUser);
        $action = $service->prepareAction('create_transaction', [
            'amount' => 100,
            'flow_type' => 'expense',
            'description' => 'Test expense',
        ]);

        $response = $this->postJson("/api/addy/actions/{$action->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'result',
            ]);

        // Verify action was executed
        $action->refresh();
        $this->assertEquals('executed', $action->status);
        $this->assertNotNull($action->executed_at);
    }

    /** @test */
    public function user_can_cancel_action(): void
    {
        $this->authenticate();

        $action = \App\Models\AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_transaction',
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/addy/actions/{$action->id}/cancel");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify action was cancelled
        $action->refresh();
        $this->assertEquals('cancelled', $action->status);
    }

    /** @test */
    public function user_can_rate_action(): void
    {
        $this->authenticate();

        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_transaction',
            'status' => 'executed',
            'was_successful' => true,
        ]);

        $response = $this->postJson("/api/addy/actions/{$action->id}/rate", [
            'rating' => 5,
            'feedback' => 'Great job!',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify rating was saved
        $action->refresh();
        $this->assertEquals(5, $action->user_rating);
    }

    /** @test */
    public function user_can_get_action_history(): void
    {
        $this->authenticate();

        // Create some actions
        AddyAction::factory()->count(5)->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $response = $this->getJson('/api/addy/actions/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'action_type',
                    'status',
                    'created_at',
                ],
            ]);

        $this->assertCount(5, $response->json());
    }

    /** @test */
    public function user_can_get_suggested_actions(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/addy/actions/suggestions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'action_type',
                    'category',
                    'title',
                    'description',
                    'confidence',
                ],
            ]);
    }

    /** @test */
    public function user_cannot_confirm_other_users_action(): void
    {
        $this->authenticate();

        $otherUser = \App\Models\User::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $otherUser->id,
            'action_type' => 'create_transaction',
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/addy/actions/{$action->id}/confirm");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_confirm_already_executed_action(): void
    {
        $this->authenticate();

        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_transaction',
            'status' => 'executed',
        ]);

        $response = $this->postJson("/api/addy/actions/{$action->id}/confirm");

        $response->assertStatus(400);
    }

    /** @test */
    public function action_requires_authentication(): void
    {
        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $response = $this->postJson("/api/addy/actions/{$action->id}/confirm");

        $response->assertStatus(401);
    }

    /** @test */
    public function rating_must_be_valid(): void
    {
        $this->authenticate();

        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'status' => 'executed',
        ]);

        // Rating must be between 1 and 5
        $response = $this->postJson("/api/addy/actions/{$action->id}/rate", [
            'rating' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /** @test */
    public function action_history_is_scoped_to_user(): void
    {
        $this->authenticate();

        // Create actions for this user
        AddyAction::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        // Create actions for another user
        $otherUser = \App\Models\User::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);
        AddyAction::factory()->count(2)->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/addy/actions/history');

        // Should only return current user's actions
        $this->assertCount(3, $response->json());
    }
}

