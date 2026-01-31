<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AddyChatMessage;
use App\Models\Organization;
use App\Models\User;

class AddyChatMessageTest extends TestCase
{
    /** @test */
    public function it_can_create_chat_message_with_factory(): void
    {
        $message = AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertDatabaseHas('addy_chat_messages', [
            'id' => $message->id,
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $message = AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertInstanceOf(Organization::class, $message->organization);
        $this->assertEquals($this->testOrganization->id, $message->organization->id);
    }

    /** @test */
    public function it_belongs_to_user(): void
    {
        $message = AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertInstanceOf(User::class, $message->user);
        $this->assertEquals($this->testUser->id, $message->user->id);
    }

    /** @test */
    public function it_casts_metadata_to_array(): void
    {
        $metadata = ['intent' => 'greeting', 'confidence' => 0.95];

        $message = AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($message->metadata);
        $this->assertEquals('greeting', $message->metadata['intent']);
        $this->assertEquals(0.95, $message->metadata['confidence']);
    }

    /** @test */
    public function it_can_create_user_role_message(): void
    {
        $message = AddyChatMessage::factory()->user()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertEquals('user', $message->role);
    }

    /** @test */
    public function it_can_create_assistant_role_message(): void
    {
        $message = AddyChatMessage::factory()->assistant()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertEquals('assistant', $message->role);
    }

    /** @test */
    public function it_retrieves_recent_history_in_correct_order(): void
    {
        // Create messages with different timestamps
        $message1 = AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'content' => 'First message',
            'created_at' => now()->subMinutes(10),
        ]);

        $message2 = AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'content' => 'Second message',
            'created_at' => now()->subMinutes(5),
        ]);

        $message3 = AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'content' => 'Third message',
            'created_at' => now(),
        ]);

        $history = AddyChatMessage::getRecentHistory(
            $this->testOrganization->id,
            $this->testUser->id,
            10
        );

        $this->assertCount(3, $history);
        // Should be in chronological order (oldest first after reverse)
        $this->assertEquals('First message', $history->first()->content);
        $this->assertEquals('Third message', $history->last()->content);
    }

    /** @test */
    public function it_limits_recent_history(): void
    {
        // Create 5 messages
        for ($i = 1; $i <= 5; $i++) {
            AddyChatMessage::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'user_id' => $this->testUser->id,
                'content' => "Message $i",
                'created_at' => now()->subMinutes(6 - $i),
            ]);
        }

        $history = AddyChatMessage::getRecentHistory(
            $this->testOrganization->id,
            $this->testUser->id,
            3
        );

        $this->assertCount(3, $history);
        // Should get the 3 most recent messages
        $this->assertEquals('Message 3', $history->first()->content);
        $this->assertEquals('Message 5', $history->last()->content);
    }

    /** @test */
    public function it_filters_history_by_organization(): void
    {
        $otherOrg = Organization::factory()->create();

        AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'content' => 'Test org message',
        ]);

        AddyChatMessage::factory()->create([
            'organization_id' => $otherOrg->id,
            'user_id' => $this->testUser->id,
            'content' => 'Other org message',
        ]);

        $history = AddyChatMessage::getRecentHistory(
            $this->testOrganization->id,
            $this->testUser->id,
            10
        );

        $this->assertCount(1, $history);
        $this->assertEquals('Test org message', $history->first()->content);
    }

    /** @test */
    public function it_filters_history_by_user(): void
    {
        $otherUser = User::factory()->create();

        AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'content' => 'Test user message',
        ]);

        AddyChatMessage::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $otherUser->id,
            'content' => 'Other user message',
        ]);

        $history = AddyChatMessage::getRecentHistory(
            $this->testOrganization->id,
            $this->testUser->id,
            10
        );

        $this->assertCount(1, $history);
        $this->assertEquals('Test user message', $history->first()->content);
    }

    /** @test */
    public function it_returns_empty_collection_when_no_history(): void
    {
        $history = AddyChatMessage::getRecentHistory(
            $this->testOrganization->id,
            $this->testUser->id,
            10
        );

        $this->assertCount(0, $history);
        $this->assertTrue($history->isEmpty());
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $message = new AddyChatMessage();

        $this->assertTrue(in_array('organization_id', $message->getFillable()));
        $this->assertTrue(in_array('user_id', $message->getFillable()));
        $this->assertTrue(in_array('role', $message->getFillable()));
        $this->assertTrue(in_array('content', $message->getFillable()));
        $this->assertTrue(in_array('metadata', $message->getFillable()));
    }
}
