<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AddyActionPattern;

class AddyActionPatternTest extends TestCase
{
    /** @test */
    public function it_can_create_action_pattern(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
        ]);

        $this->assertDatabaseHas('addy_action_patterns', [
            'id' => $pattern->id,
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
        ]);
    }

    /** @test */
    public function it_casts_successful_contexts_to_array(): void
    {
        $contexts = [
            ['date' => '2025-01-15', 'amount' => 1000],
            ['date' => '2025-01-16', 'amount' => 2000],
        ];

        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'successful_contexts' => $contexts,
        ]);

        $this->assertIsArray($pattern->successful_contexts);
        $this->assertCount(2, $pattern->successful_contexts);
    }

    /** @test */
    public function it_casts_failed_contexts_to_array(): void
    {
        $contexts = [
            ['date' => '2025-01-15', 'reason' => 'cancelled'],
        ];

        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'failed_contexts' => $contexts,
        ]);

        $this->assertIsArray($pattern->failed_contexts);
        $this->assertCount(1, $pattern->failed_contexts);
    }

    /** @test */
    public function get_or_create_returns_existing_pattern(): void
    {
        $existing = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_suggested' => 5,
        ]);

        $pattern = AddyActionPattern::getOrCreate(
            $this->testOrganization->id,
            $this->testUser->id,
            'create_invoice'
        );

        $this->assertEquals($existing->id, $pattern->id);
        $this->assertEquals(5, $pattern->times_suggested);
    }

    /** @test */
    public function get_or_create_creates_new_pattern(): void
    {
        $pattern = AddyActionPattern::getOrCreate(
            $this->testOrganization->id,
            $this->testUser->id,
            'send_reminder'
        );

        $this->assertNotNull($pattern->id);
        $this->assertEquals('send_reminder', $pattern->action_type);
        $this->assertEquals(0, $pattern->times_suggested);
    }

    /** @test */
    public function record_suggestion_increments_count(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_suggested' => 0,
        ]);

        $pattern->recordSuggestion();

        $this->assertEquals(1, $pattern->fresh()->times_suggested);

        $pattern->recordSuggestion();

        $this->assertEquals(2, $pattern->fresh()->times_suggested);
    }

    /** @test */
    public function record_confirmation_increments_count(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_confirmed' => 0,
        ]);

        $pattern->recordConfirmation();

        $this->assertEquals(1, $pattern->fresh()->times_confirmed);
    }

    /** @test */
    public function record_confirmation_stores_context(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'successful_contexts' => [],
        ]);

        $pattern->recordConfirmation(['amount' => 1000, 'customer' => 'test']);
        $pattern->refresh();

        $this->assertCount(1, $pattern->successful_contexts);
        $this->assertEquals(1000, $pattern->successful_contexts[0]['amount']);
        $this->assertArrayHasKey('date', $pattern->successful_contexts[0]);
    }

    /** @test */
    public function record_confirmation_keeps_only_last_10_contexts(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'successful_contexts' => [],
        ]);

        // Record 12 confirmations
        for ($i = 1; $i <= 12; $i++) {
            $pattern->recordConfirmation(['index' => $i]);
        }

        $pattern->refresh();

        $this->assertCount(10, $pattern->successful_contexts);
        // Should have the last 10 (indices 3-12)
        $this->assertEquals(3, $pattern->successful_contexts[0]['index']);
        $this->assertEquals(12, $pattern->successful_contexts[9]['index']);
    }

    /** @test */
    public function record_rejection_increments_count(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_rejected' => 0,
        ]);

        $pattern->recordRejection();

        $this->assertEquals(1, $pattern->fresh()->times_rejected);
    }

    /** @test */
    public function record_rejection_stores_context(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'failed_contexts' => [],
        ]);

        $pattern->recordRejection(['reason' => 'wrong_customer']);
        $pattern->refresh();

        $this->assertCount(1, $pattern->failed_contexts);
        $this->assertEquals('wrong_customer', $pattern->failed_contexts[0]['reason']);
        $this->assertArrayHasKey('date', $pattern->failed_contexts[0]);
    }

    /** @test */
    public function record_rejection_keeps_only_last_10_contexts(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'failed_contexts' => [],
        ]);

        // Record 12 rejections
        for ($i = 1; $i <= 12; $i++) {
            $pattern->recordRejection(['index' => $i]);
        }

        $pattern->refresh();

        $this->assertCount(10, $pattern->failed_contexts);
    }

    /** @test */
    public function record_success_increments_count(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_successful' => 0,
        ]);

        $pattern->recordSuccess();

        $this->assertEquals(1, $pattern->fresh()->times_successful);
    }

    /** @test */
    public function record_success_updates_average_rating(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_successful' => 0,
            'avg_rating' => 0,
        ]);

        $pattern->recordSuccess(5);
        $pattern->refresh();

        $this->assertEquals(5, (float) $pattern->avg_rating);

        $pattern->recordSuccess(3);
        $pattern->refresh();

        // Average of 5 and 3 = 4
        $this->assertEquals(4, (float) $pattern->avg_rating);
    }

    /** @test */
    public function get_confidence_returns_half_with_insufficient_data(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_confirmed' => 1,
            'times_rejected' => 1,
        ]);

        $this->assertEquals(0.5, $pattern->getConfidence());
    }

    /** @test */
    public function get_confidence_calculates_correctly_with_all_confirmed(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_confirmed' => 10,
            'times_rejected' => 0,
            'times_successful' => 10, // 100% success
        ]);

        // confirmRate = 10/10 = 1.0
        // successRate = 10/10 = 1.0
        // confidence = (1.0 * 0.6) + (1.0 * 0.4) = 1.0
        $this->assertEquals(1.0, $pattern->getConfidence());
    }

    /** @test */
    public function get_confidence_calculates_correctly_with_mixed_results(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_confirmed' => 6,
            'times_rejected' => 4,
            'times_successful' => 3, // 50% success rate
        ]);

        // confirmRate = 6/10 = 0.6
        // successRate = 3/6 = 0.5
        // confidence = (0.6 * 0.6) + (0.5 * 0.4) = 0.36 + 0.2 = 0.56
        $this->assertEquals(0.56, $pattern->getConfidence());
    }

    /** @test */
    public function get_confidence_handles_zero_confirmations(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_confirmed' => 0,
            'times_rejected' => 5,
            'times_successful' => 0,
        ]);

        // confirmRate = 0/5 = 0
        // successRate = 0 (since times_confirmed is 0)
        // confidence = (0 * 0.6) + (0 * 0.4) = 0
        $this->assertEquals(0.0, $pattern->getConfidence());
    }

    /** @test */
    public function should_suggest_returns_true_when_confidence_high(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_confirmed' => 10,
            'times_rejected' => 0,
            'times_successful' => 10,
        ]);

        $this->assertTrue($pattern->shouldSuggest());
    }

    /** @test */
    public function should_suggest_returns_false_when_confidence_low(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_confirmed' => 1,
            'times_rejected' => 10,
            'times_successful' => 0,
        ]);

        $this->assertFalse($pattern->shouldSuggest());
    }

    /** @test */
    public function should_suggest_returns_false_with_insufficient_data(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_confirmed' => 1,
            'times_rejected' => 1, // Only 2 total, less than 3
            'times_successful' => 1,
        ]);

        // With insufficient data, confidence is 0.5, which is < 0.6
        $this->assertFalse($pattern->shouldSuggest());
    }

    /** @test */
    public function should_suggest_returns_true_at_threshold(): void
    {
        $pattern = AddyActionPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_type' => 'create_invoice',
            'times_confirmed' => 6,
            'times_rejected' => 4,
            'times_successful' => 6, // 100% success rate of confirmed
        ]);

        // confirmRate = 6/10 = 0.6
        // successRate = 6/6 = 1.0
        // confidence = (0.6 * 0.6) + (1.0 * 0.4) = 0.36 + 0.4 = 0.76
        $this->assertTrue($pattern->shouldSuggest());
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $pattern = new AddyActionPattern();
        $fillable = $pattern->getFillable();

        $expected = [
            'organization_id',
            'user_id',
            'action_type',
            'times_suggested',
            'times_confirmed',
            'times_rejected',
            'times_successful',
            'avg_rating',
            'successful_contexts',
            'failed_contexts',
        ];

        foreach ($expected as $field) {
            $this->assertTrue(in_array($field, $fillable), "Field $field should be fillable");
        }
    }

    /** @test */
    public function it_stores_different_action_types(): void
    {
        $actionTypes = ['create_invoice', 'send_reminder', 'adjust_budget', 'create_transaction'];

        foreach ($actionTypes as $type) {
            $pattern = AddyActionPattern::create([
                'organization_id' => $this->testOrganization->id,
                'user_id' => $this->testUser->id,
                'action_type' => $type,
            ]);

            $this->assertEquals($type, $pattern->action_type);

            $pattern->delete();
        }
    }
}
