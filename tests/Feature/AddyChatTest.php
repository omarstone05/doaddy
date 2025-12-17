<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AddyChatMessage;
use App\Models\AddyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddyChatTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_send_message_to_addy(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Hello Addy, how are you?',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'quick_actions',
                'action',
            ]);

        // Verify message was saved
        $this->assertDatabaseHas('addy_chat_messages', [
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'role' => 'user',
            'content' => 'Hello Addy, how are you?',
        ]);
    }

    /** @test */
    public function user_can_get_chat_history(): void
    {
        $this->authenticate();

        // Create some chat messages
        AddyChatMessage::factory()->count(5)->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $response = $this->getJson('/api/addy/chat/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'role',
                    'content',
                    'created_at',
                ],
            ]);

        $this->assertCount(5, $response->json());
    }

    /** @test */
    public function user_can_clear_chat_history(): void
    {
        $this->authenticate();

        // Create some chat messages
        AddyChatMessage::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $response = $this->deleteJson('/api/addy/chat/history');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify messages were deleted
        $this->assertDatabaseMissing('addy_chat_messages', [
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);
    }

    /** @test */
    public function chat_requires_authentication(): void
    {
        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function chat_message_is_required(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/addy/chat', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function chat_handles_action_requests(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'Create a transaction for $100 expense',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'quick_actions',
                'action',
            ]);

        // Verify action was created
        $this->assertDatabaseHas('addy_actions', [
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_transaction',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function chat_returns_quick_actions(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/addy/chat', [
            'message' => 'What is my cash position?',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'quick_actions',
            ]);
    }

    /** @test */
    public function chat_history_is_scoped_to_user(): void
    {
        $this->authenticate();

        // Create messages for this user
        AddyChatMessage::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        // Create messages for another user
        $otherUser = \App\Models\User::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);
        AddyChatMessage::factory()->count(2)->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/addy/chat/history');

        // Should only return current user's messages
        $this->assertCount(3, $response->json());
    }
}

