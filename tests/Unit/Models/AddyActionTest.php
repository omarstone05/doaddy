<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AddyAction;
use App\Models\AddyChatMessage;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;

class AddyActionTest extends TestCase
{
    /** @test */
    public function it_can_create_action_with_factory(): void
    {
        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertDatabaseHas('addy_actions', [
            'id' => $action->id,
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertInstanceOf(Organization::class, $action->organization);
        $this->assertEquals($this->testOrganization->id, $action->organization->id);
    }

    /** @test */
    public function it_belongs_to_user(): void
    {
        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertInstanceOf(User::class, $action->user);
        $this->assertEquals($this->testUser->id, $action->user->id);
    }

    /** @test */
    public function it_belongs_to_chat_message(): void
    {
        $chatMessage = AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'chat_message_id' => $chatMessage->id,
        ]);

        $this->assertInstanceOf(AddyChatMessage::class, $action->chatMessage);
        $this->assertEquals($chatMessage->id, $action->chatMessage->id);
    }

    /** @test */
    public function it_casts_parameters_to_array(): void
    {
        $parameters = [
            'customer_id' => 'uuid-123',
            'amount' => 1500,
            'description' => 'Test invoice',
        ];

        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'parameters' => $parameters,
        ]);

        $this->assertIsArray($action->parameters);
        $this->assertEquals('uuid-123', $action->parameters['customer_id']);
        $this->assertEquals(1500, $action->parameters['amount']);
    }

    /** @test */
    public function it_casts_preview_data_to_array(): void
    {
        $previewData = [
            'title' => 'Invoice Preview',
            'items' => [
                ['name' => 'Item 1', 'price' => 100],
                ['name' => 'Item 2', 'price' => 200],
            ],
        ];

        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'preview_data' => $previewData,
        ]);

        $this->assertIsArray($action->preview_data);
        $this->assertEquals('Invoice Preview', $action->preview_data['title']);
        $this->assertCount(2, $action->preview_data['items']);
    }

    /** @test */
    public function it_casts_result_to_array(): void
    {
        $result = [
            'success' => true,
            'invoice_id' => 'inv-123',
            'message' => 'Invoice created successfully',
        ];

        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'result' => $result,
        ]);

        $this->assertIsArray($action->result);
        $this->assertTrue($action->result['success']);
        $this->assertEquals('inv-123', $action->result['invoice_id']);
    }

    /** @test */
    public function it_casts_datetime_fields(): void
    {
        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'confirmed_at' => now(),
            'executed_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $action->confirmed_at);
        $this->assertInstanceOf(Carbon::class, $action->executed_at);
    }

    /** @test */
    public function it_casts_was_successful_to_boolean(): void
    {
        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'was_successful' => true,
        ]);

        $this->assertIsBool($action->was_successful);
        $this->assertTrue($action->was_successful);
    }

    /** @test */
    public function confirm_updates_status_and_timestamp(): void
    {
        $action = AddyAction::factory()->pending()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertEquals('pending', $action->status);
        $this->assertNull($action->confirmed_at);

        $action->confirm();
        $action->refresh();

        $this->assertEquals('confirmed', $action->status);
        $this->assertNotNull($action->confirmed_at);
        $this->assertInstanceOf(Carbon::class, $action->confirmed_at);
    }

    /** @test */
    public function mark_executed_with_success_updates_correctly(): void
    {
        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'status' => 'confirmed',
        ]);

        $result = ['invoice_id' => 'inv-123', 'message' => 'Created'];

        $action->markExecuted($result, true);
        $action->refresh();

        $this->assertEquals('executed', $action->status);
        $this->assertNotNull($action->executed_at);
        $this->assertTrue($action->was_successful);
        $this->assertEquals('inv-123', $action->result['invoice_id']);
    }

    /** @test */
    public function mark_executed_with_failure_updates_correctly(): void
    {
        $action = AddyAction::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'status' => 'confirmed',
        ]);

        $result = ['error' => 'Validation failed', 'code' => 422];

        $action->markExecuted($result, false);
        $action->refresh();

        $this->assertEquals('failed', $action->status);
        $this->assertNotNull($action->executed_at);
        $this->assertFalse($action->was_successful);
        $this->assertEquals('Validation failed', $action->result['error']);
    }

    /** @test */
    public function fail_updates_status_and_error(): void
    {
        $action = AddyAction::factory()->pending()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $errorMessage = 'Database connection failed';

        $action->fail($errorMessage);
        $action->refresh();

        $this->assertEquals('failed', $action->status);
        $this->assertEquals($errorMessage, $action->error_message);
        $this->assertFalse($action->was_successful);
    }

    /** @test */
    public function cancel_updates_status(): void
    {
        $action = AddyAction::factory()->pending()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $action->cancel();
        $action->refresh();

        $this->assertEquals('cancelled', $action->status);
    }

    /** @test */
    public function it_can_create_pending_action(): void
    {
        $action = AddyAction::factory()->pending()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertEquals('pending', $action->status);
    }

    /** @test */
    public function it_can_create_executed_action(): void
    {
        $action = AddyAction::factory()->executed()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertEquals('executed', $action->status);
        $this->assertNotNull($action->executed_at);
        $this->assertTrue($action->was_successful);
    }

    /** @test */
    public function it_has_correct_action_types(): void
    {
        $actionTypes = ['create_transaction', 'send_invoice_reminders', 'adjust_budget', 'create_invoice'];

        foreach ($actionTypes as $type) {
            $action = AddyAction::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'user_id' => $this->testUser->id,
                'action_type' => $type,
            ]);

            $this->assertEquals($type, $action->action_type);
        }
    }

    /** @test */
    public function it_has_correct_categories(): void
    {
        $categories = ['money', 'sales', 'people', 'inventory'];

        foreach ($categories as $category) {
            $action = AddyAction::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'user_id' => $this->testUser->id,
                'category' => $category,
            ]);

            $this->assertEquals($category, $action->category);
        }
    }

    /** @test */
    public function it_has_correct_statuses(): void
    {
        $statuses = ['pending', 'confirmed', 'executed', 'cancelled', 'failed'];

        foreach ($statuses as $status) {
            $action = AddyAction::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'user_id' => $this->testUser->id,
                'status' => $status,
            ]);

            $this->assertEquals($status, $action->status);
        }
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $action = new AddyAction();
        $fillable = $action->getFillable();

        $expected = [
            'organization_id',
            'user_id',
            'chat_message_id',
            'action_type',
            'category',
            'status',
            'parameters',
            'preview_data',
            'result',
            'confirmed_at',
            'executed_at',
            'error_message',
            'was_successful',
            'user_rating',
        ];

        foreach ($expected as $field) {
            $this->assertTrue(in_array($field, $fillable), "Field $field should be fillable");
        }
    }

    /** @test */
    public function it_can_store_user_rating(): void
    {
        $action = AddyAction::factory()->executed()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'user_rating' => 5,
        ]);

        $this->assertEquals(5, $action->user_rating);
    }
}
