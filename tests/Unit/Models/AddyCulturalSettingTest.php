<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AddyCulturalSetting;
use App\Models\Organization;
use Carbon\Carbon;

class AddyCulturalSettingTest extends TestCase
{
    /** @test */
    public function it_can_create_cultural_setting(): void
    {
        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'tone' => 'professional',
            'timezone' => 'Africa/Lusaka',
        ]);

        $this->assertDatabaseHas('addy_cultural_settings', [
            'id' => $setting->id,
            'organization_id' => $this->testOrganization->id,
            'tone' => 'professional',
        ]);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertInstanceOf(Organization::class, $setting->organization);
        $this->assertEquals($this->testOrganization->id, $setting->organization->id);
    }

    /** @test */
    public function it_casts_weekly_themes_to_array(): void
    {
        $themes = [
            'monday' => 'Deep Work',
            'tuesday' => 'Collaboration',
            'friday' => 'Review',
        ];

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'weekly_themes' => $themes,
        ]);

        $this->assertIsArray($setting->weekly_themes);
        $this->assertEquals('Deep Work', $setting->weekly_themes['monday']);
    }

    /** @test */
    public function it_casts_blocked_times_to_array(): void
    {
        $blockedTimes = [
            ['day' => 'monday', 'start' => '09:00', 'end' => '10:00', 'reason' => 'Team meeting'],
            ['day' => 'friday', 'start' => '14:00', 'end' => '15:00', 'reason' => 'Review'],
        ];

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'blocked_times' => $blockedTimes,
        ]);

        $this->assertIsArray($setting->blocked_times);
        $this->assertCount(2, $setting->blocked_times);
        $this->assertEquals('Team meeting', $setting->blocked_times[0]['reason']);
    }

    /** @test */
    public function it_casts_boolean_fields(): void
    {
        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'enable_predictions' => true,
            'enable_proactive_suggestions' => false,
        ]);

        $this->assertIsBool($setting->enable_predictions);
        $this->assertIsBool($setting->enable_proactive_suggestions);
        $this->assertTrue($setting->enable_predictions);
        $this->assertFalse($setting->enable_proactive_suggestions);
    }

    /** @test */
    public function get_or_create_returns_existing_setting(): void
    {
        $existing = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'tone' => 'casual',
            'max_daily_suggestions' => 10,
        ]);

        $setting = AddyCulturalSetting::getOrCreate($this->testOrganization->id);

        $this->assertEquals($existing->id, $setting->id);
        $this->assertEquals('casual', $setting->tone);
        $this->assertEquals(10, $setting->max_daily_suggestions);
    }

    /** @test */
    public function get_or_create_creates_new_setting_with_defaults(): void
    {
        $setting = AddyCulturalSetting::getOrCreate($this->testOrganization->id);

        $this->assertNotNull($setting->id);
        $this->assertEquals('professional', $setting->tone);
        $this->assertEquals('UTC', $setting->timezone);
        $this->assertEquals(5, $setting->max_daily_suggestions);
    }

    /** @test */
    public function is_in_quiet_hours_returns_false_when_not_set(): void
    {
        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
        ]);

        $this->assertFalse($setting->isInQuietHours());
    }

    /** @test */
    public function is_in_quiet_hours_returns_false_when_only_start_set(): void
    {
        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => null,
        ]);

        $this->assertFalse($setting->isInQuietHours());
    }

    /** @test */
    public function is_in_quiet_hours_returns_true_during_daytime_quiet_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 14:30:00', 'UTC'));

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'timezone' => 'UTC',
            'quiet_hours_start' => '12:00',
            'quiet_hours_end' => '18:00',
        ]);

        $this->assertTrue($setting->isInQuietHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function is_in_quiet_hours_returns_false_outside_daytime_quiet_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 10:00:00', 'UTC'));

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'timezone' => 'UTC',
            'quiet_hours_start' => '12:00',
            'quiet_hours_end' => '18:00',
        ]);

        $this->assertFalse($setting->isInQuietHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function is_in_quiet_hours_handles_overnight_period_before_midnight(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 23:30:00', 'UTC'));

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'timezone' => 'UTC',
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '06:00',
        ]);

        $this->assertTrue($setting->isInQuietHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function is_in_quiet_hours_handles_overnight_period_after_midnight(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 03:00:00', 'UTC'));

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'timezone' => 'UTC',
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '06:00',
        ]);

        $this->assertTrue($setting->isInQuietHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function is_in_quiet_hours_returns_false_during_day_for_overnight_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 12:00:00', 'UTC'));

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'timezone' => 'UTC',
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '06:00',
        ]);

        $this->assertFalse($setting->isInQuietHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function is_in_quiet_hours_respects_timezone(): void
    {
        // Set current time to noon UTC, which is 2pm in Africa/Lusaka (UTC+2)
        Carbon::setTestNow(Carbon::parse('2025-01-20 12:00:00', 'UTC'));

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'timezone' => 'Africa/Lusaka',
            'quiet_hours_start' => '13:00', // 1pm local
            'quiet_hours_end' => '15:00',   // 3pm local
        ]);

        // Local time is 14:00 (2pm), which is within quiet hours
        $this->assertTrue($setting->isInQuietHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function is_in_quiet_hours_at_boundary_start(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 22:00:00', 'UTC'));

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'timezone' => 'UTC',
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '06:00',
        ]);

        $this->assertTrue($setting->isInQuietHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function is_in_quiet_hours_at_boundary_end(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 06:00:00', 'UTC'));

        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'timezone' => 'UTC',
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '06:00',
        ]);

        $this->assertTrue($setting->isInQuietHours());

        Carbon::setTestNow();
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $setting = new AddyCulturalSetting();
        $fillable = $setting->getFillable();

        $expected = [
            'organization_id',
            'weekly_themes',
            'blocked_times',
            'timezone',
            'tone',
            'enable_predictions',
            'enable_proactive_suggestions',
            'max_daily_suggestions',
            'quiet_hours_start',
            'quiet_hours_end',
        ];

        foreach ($expected as $field) {
            $this->assertTrue(in_array($field, $fillable), "Field $field should be fillable");
        }
    }

    /** @test */
    public function it_stores_different_tones(): void
    {
        $tones = ['professional', 'casual', 'friendly', 'formal'];

        foreach ($tones as $tone) {
            $setting = AddyCulturalSetting::create([
                'organization_id' => $this->testOrganization->id,
                'tone' => $tone,
            ]);

            $this->assertEquals($tone, $setting->tone);

            $setting->delete();
        }
    }

    /** @test */
    public function it_stores_max_daily_suggestions(): void
    {
        $setting = AddyCulturalSetting::create([
            'organization_id' => $this->testOrganization->id,
            'max_daily_suggestions' => 15,
        ]);

        $this->assertEquals(15, $setting->max_daily_suggestions);
    }

    /** @test */
    public function it_stores_various_timezones(): void
    {
        $timezones = ['UTC', 'Africa/Lusaka', 'America/New_York', 'Europe/London', 'Asia/Tokyo'];

        foreach ($timezones as $timezone) {
            $setting = AddyCulturalSetting::create([
                'organization_id' => $this->testOrganization->id,
                'timezone' => $timezone,
            ]);

            $this->assertEquals($timezone, $setting->timezone);

            $setting->delete();
        }
    }
}
