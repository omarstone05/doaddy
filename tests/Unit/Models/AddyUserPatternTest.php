<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AddyUserPattern;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;

class AddyUserPatternTest extends TestCase
{
    /** @test */
    public function it_can_create_user_pattern(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'work_style' => 'balanced',
        ]);

        $this->assertDatabaseHas('addy_user_patterns', [
            'id' => $pattern->id,
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertInstanceOf(Organization::class, $pattern->organization);
        $this->assertEquals($this->testOrganization->id, $pattern->organization->id);
    }

    /** @test */
    public function it_belongs_to_user(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);

        $this->assertInstanceOf(User::class, $pattern->user);
        $this->assertEquals($this->testUser->id, $pattern->user->id);
    }

    /** @test */
    public function it_casts_array_fields(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'weekly_rhythm' => ['monday' => ['theme' => 'Work', 'focus' => 'planning']],
            'peak_hours' => [9, 10, 11],
            'section_preferences' => ['money' => 5, 'sales' => 3],
            'action_patterns' => ['invoice_complete' => 10],
            'dismissed_insight_types' => ['budget_warning'],
        ]);

        $this->assertIsArray($pattern->weekly_rhythm);
        $this->assertIsArray($pattern->peak_hours);
        $this->assertIsArray($pattern->section_preferences);
        $this->assertIsArray($pattern->action_patterns);
        $this->assertIsArray($pattern->dismissed_insight_types);
    }

    /** @test */
    public function it_casts_adhd_mode_to_boolean(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'adhd_mode' => true,
        ]);

        $this->assertIsBool($pattern->adhd_mode);
        $this->assertTrue($pattern->adhd_mode);
    }

    /** @test */
    public function get_or_create_returns_existing_pattern(): void
    {
        $existingPattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'work_style' => 'focused',
        ]);

        $pattern = AddyUserPattern::getOrCreate(
            $this->testOrganization->id,
            $this->testUser->id
        );

        $this->assertEquals($existingPattern->id, $pattern->id);
        $this->assertEquals('focused', $pattern->work_style);
    }

    /** @test */
    public function get_or_create_creates_new_pattern_with_defaults(): void
    {
        $pattern = AddyUserPattern::getOrCreate(
            $this->testOrganization->id,
            $this->testUser->id
        );

        $this->assertNotNull($pattern->id);
        $this->assertEquals('balanced', $pattern->work_style);
        $this->assertEquals([9, 10, 11, 14, 15, 16], $pattern->peak_hours);
        $this->assertArrayHasKey('monday', $pattern->weekly_rhythm);
        $this->assertArrayHasKey('friday', $pattern->weekly_rhythm);
    }

    /** @test */
    public function get_or_create_sets_default_weekly_rhythm(): void
    {
        $pattern = AddyUserPattern::getOrCreate(
            $this->testOrganization->id,
            $this->testUser->id
        );

        $rhythm = $pattern->weekly_rhythm;

        $this->assertEquals('Deep Work', $rhythm['monday']['theme']);
        $this->assertEquals('planning', $rhythm['monday']['focus']);
        $this->assertEquals('Build', $rhythm['tuesday']['theme']);
        $this->assertEquals('Creative', $rhythm['friday']['theme']);
        $this->assertEquals('Rest', $rhythm['saturday']['theme']);
    }

    /** @test */
    public function get_today_theme_returns_correct_theme(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 10:00:00')); // Monday

        $pattern = AddyUserPattern::getOrCreate(
            $this->testOrganization->id,
            $this->testUser->id
        );

        $theme = $pattern->getTodayTheme();

        $this->assertEquals('Deep Work', $theme['theme']);
        $this->assertEquals('planning', $theme['focus']);

        Carbon::setTestNow();
    }

    /** @test */
    public function get_today_theme_returns_default_when_day_missing(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'weekly_rhythm' => [], // Empty rhythm
        ]);

        $theme = $pattern->getTodayTheme();

        $this->assertEquals('Work', $theme['theme']);
        $this->assertEquals('general', $theme['focus']);
    }

    /** @test */
    public function is_in_peak_hours_returns_true_during_peak(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 10:30:00')); // 10am

        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'peak_hours' => [9, 10, 11, 14, 15],
        ]);

        $this->assertTrue($pattern->isInPeakHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function is_in_peak_hours_returns_false_outside_peak(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 20:00:00')); // 8pm

        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'peak_hours' => [9, 10, 11, 14, 15],
        ]);

        $this->assertFalse($pattern->isInPeakHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function is_in_peak_hours_handles_null_peak_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 10:00:00'));

        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'peak_hours' => null,
        ]);

        $this->assertFalse($pattern->isInPeakHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function record_section_visit_increments_count(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'section_preferences' => [],
        ]);

        $pattern->recordSectionVisit('money');
        $pattern->refresh();

        $this->assertEquals(1, $pattern->section_preferences['money']);

        $pattern->recordSectionVisit('money');
        $pattern->refresh();

        $this->assertEquals(2, $pattern->section_preferences['money']);
    }

    /** @test */
    public function record_section_visit_handles_multiple_sections(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'section_preferences' => [],
        ]);

        $pattern->recordSectionVisit('money');
        $pattern->recordSectionVisit('sales');
        $pattern->recordSectionVisit('money');
        $pattern->refresh();

        $this->assertEquals(2, $pattern->section_preferences['money']);
        $this->assertEquals(1, $pattern->section_preferences['sales']);
    }

    /** @test */
    public function record_section_visit_handles_existing_preferences(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'section_preferences' => ['money' => 5, 'sales' => 3],
        ]);

        $pattern->recordSectionVisit('money');
        $pattern->refresh();

        $this->assertEquals(6, $pattern->section_preferences['money']);
        $this->assertEquals(3, $pattern->section_preferences['sales']);
    }

    /** @test */
    public function record_insight_action_increments_count(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_patterns' => [],
        ]);

        $pattern->recordInsightAction('budget_alert', 'dismissed');
        $pattern->refresh();

        $this->assertEquals(1, $pattern->action_patterns['budget_alert_dismissed']);

        $pattern->recordInsightAction('budget_alert', 'dismissed');
        $pattern->refresh();

        $this->assertEquals(2, $pattern->action_patterns['budget_alert_dismissed']);
    }

    /** @test */
    public function record_insight_action_tracks_different_combinations(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'action_patterns' => [],
        ]);

        $pattern->recordInsightAction('invoice_reminder', 'completed');
        $pattern->recordInsightAction('invoice_reminder', 'dismissed');
        $pattern->recordInsightAction('budget_alert', 'completed');
        $pattern->refresh();

        $this->assertEquals(1, $pattern->action_patterns['invoice_reminder_completed']);
        $this->assertEquals(1, $pattern->action_patterns['invoice_reminder_dismissed']);
        $this->assertEquals(1, $pattern->action_patterns['budget_alert_completed']);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $pattern = new AddyUserPattern();
        $fillable = $pattern->getFillable();

        $expected = [
            'organization_id',
            'user_id',
            'weekly_rhythm',
            'peak_hours',
            'section_preferences',
            'avg_response_time',
            'action_patterns',
            'dismissed_insight_types',
            'work_style',
            'adhd_mode',
            'preferred_task_chunk_size',
        ];

        foreach ($expected as $field) {
            $this->assertTrue(in_array($field, $fillable), "Field $field should be fillable");
        }
    }

    /** @test */
    public function it_stores_work_styles(): void
    {
        $styles = ['balanced', 'focused', 'collaborative', 'analytical'];

        foreach ($styles as $style) {
            $pattern = AddyUserPattern::create([
                'organization_id' => $this->testOrganization->id,
                'user_id' => $this->testUser->id,
                'work_style' => $style,
            ]);

            $this->assertEquals($style, $pattern->work_style);

            $pattern->delete();
        }
    }

    /** @test */
    public function it_stores_avg_response_time(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'avg_response_time' => 2.5,
        ]);

        $this->assertEquals(2.5, $pattern->avg_response_time);
    }

    /** @test */
    public function it_stores_preferred_task_chunk_size(): void
    {
        $pattern = AddyUserPattern::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'preferred_task_chunk_size' => 3,
        ]);

        $this->assertEquals(3, $pattern->preferred_task_chunk_size);
    }
}
