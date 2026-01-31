<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test support tickets index is accessible
     */
    public function test_support_tickets_index_is_accessible(): void
    {
        $response = $this->authenticate()->get('/support/tickets');

        $response->assertStatus(200);
    }

    /**
     * Test support ticket create page is accessible
     */
    public function test_support_ticket_create_page_is_accessible(): void
    {
        $response = $this->authenticate()->get('/support/tickets/create');

        $response->assertStatus(200);
    }

    /**
     * Test support ticket can be created
     */
    public function test_support_ticket_can_be_created(): void
    {
        $response = $this->authenticate()->postJson('/support/tickets', [
            'subject' => 'Help with invoicing',
            'description' => 'I need help creating an invoice',
            'priority' => 'medium',
            'category' => 'billing',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('support_tickets', [
            'subject' => 'Help with invoicing',
            'user_id' => $this->testUser->id,
        ]);
    }

    /**
     * Test support ticket can be viewed
     */
    public function test_support_ticket_can_be_viewed(): void
    {
        $ticket = SupportTicket::factory()->create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
        ]);

        $response = $this->authenticate()->get("/support/tickets/{$ticket->id}");

        $response->assertStatus(200);
    }

    /**
     * Test message can be added to ticket
     */
    public function test_message_can_be_added_to_ticket(): void
    {
        $ticket = SupportTicket::factory()->create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
        ]);

        $response = $this->authenticate()->postJson("/support/tickets/{$ticket->id}/messages", [
            'message' => 'This is a follow-up message',
        ]);

        $response->assertRedirect();
    }

    /**
     * Test ticket requires subject
     */
    public function test_ticket_requires_subject(): void
    {
        $response = $this->authenticate()->postJson('/support/tickets', [
            'description' => 'Missing subject',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject']);
    }

    /**
     * Test user can only see own tickets
     */
    public function test_user_can_only_see_own_tickets(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $ticket = SupportTicket::factory()->create([
            'user_id' => $otherUser->id,
            'organization_id' => $this->testOrganization->id,
        ]);

        $response = $this->authenticate()->get("/support/tickets/{$ticket->id}");

        // Should return 404 or 403
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
