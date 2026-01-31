<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\GamificationXP;
use App\Models\GamificationBadge;
use App\Models\GamificationStreak;

class GamificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function settings_page_is_accessible(): void
    {
        $response = $this->authenticate()->get('/settings');

        $response->assertStatus(200);
    }

    /** @test */
    public function gamification_xp_can_be_totaled_per_organization(): void
    {
        // Create XP records
        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 100,
            'reason' => 'sale_recorded',
        ]);

        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 50,
            'reason' => 'invoice_issued',
        ]);

        $total = GamificationXP::where('user_id', $this->testUser->id)
            ->where('organization_id', $this->testOrganization->id)
            ->sum('xp_amount');

        $this->assertEquals(150, $total);
    }

    /** @test */
    public function gamification_level_can_be_calculated_from_xp(): void
    {
        // Test level calculation: 100 XP per level
        $xpPerLevel = config('gamification.xp_per_level', 100);

        // 0 XP = Level 1
        $level = (int) floor(0 / $xpPerLevel) + 1;
        $this->assertEquals(1, $level);

        // 99 XP = Level 1
        $level = (int) floor(99 / $xpPerLevel) + 1;
        $this->assertEquals(1, $level);

        // 100 XP = Level 2
        $level = (int) floor(100 / $xpPerLevel) + 1;
        $this->assertEquals(2, $level);

        // 250 XP = Level 3
        $level = (int) floor(250 / $xpPerLevel) + 1;
        $this->assertEquals(3, $level);
    }

    /** @test */
    public function gamification_badges_can_be_queried_by_user(): void
    {
        GamificationBadge::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'badge_type' => 'first_sale',
            'badge_name' => 'First Sale',
            'earned_at' => now(),
        ]);

        GamificationBadge::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'badge_type' => 'week_warrior',
            'badge_name' => 'Week Warrior',
            'earned_at' => now(),
        ]);

        $badges = GamificationBadge::where('user_id', $this->testUser->id)
            ->where('organization_id', $this->testOrganization->id)
            ->get();

        $this->assertCount(2, $badges);
    }

    /** @test */
    public function gamification_streak_can_be_retrieved(): void
    {
        GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 7,
            'longest_streak' => 14,
            'last_activity_date' => now()->toDateString(),
        ]);

        $streak = GamificationStreak::where('user_id', $this->testUser->id)
            ->where('organization_id', $this->testOrganization->id)
            ->first();

        $this->assertEquals(7, $streak->current_streak);
        $this->assertEquals(14, $streak->longest_streak);
    }

    /** @test */
    public function gamification_leaderboard_can_be_generated(): void
    {
        // Create XP for multiple users
        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 100,
            'reason' => 'test',
        ]);

        $leaderboard = GamificationXP::where('organization_id', $this->testOrganization->id)
            ->selectRaw('user_id, SUM(xp_amount) as total_xp')
            ->groupBy('user_id')
            ->orderByDesc('total_xp')
            ->take(10)
            ->get();

        $this->assertCount(1, $leaderboard);
        $this->assertEquals(100, $leaderboard->first()->total_xp);
    }

    /** @test */
    public function gamification_data_is_scoped_to_organization(): void
    {
        // Create XP for test organization
        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 100,
            'reason' => 'test_org',
        ]);

        // Create another organization
        $otherOrg = \App\Models\Organization::create([
            'name' => 'Other',
            'slug' => 'other-org-gamification',
        ]);

        // Create XP for other organization
        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $otherOrg->id,
            'xp_amount' => 500,
            'reason' => 'other_org',
        ]);

        $testOrgTotal = GamificationXP::where('user_id', $this->testUser->id)
            ->where('organization_id', $this->testOrganization->id)
            ->sum('xp_amount');

        $this->assertEquals(100, $testOrgTotal);
    }

    /** @test */
    public function gamification_config_has_required_keys(): void
    {
        $config = config('gamification');

        $this->assertArrayHasKey('xp_rewards', $config);
        $this->assertArrayHasKey('badges', $config);
        $this->assertArrayHasKey('xp_per_level', $config);
        $this->assertArrayHasKey('level_titles', $config);
    }

    /** @test */
    public function gamification_badges_config_has_categories(): void
    {
        $badges = config('gamification.badges', []);

        $categories = collect($badges)->pluck('category')->unique()->toArray();

        $this->assertContains('revenue', $categories);
        $this->assertContains('consistency', $categories);
    }

    /** @test */
    public function xp_progress_can_be_calculated(): void
    {
        $xpPerLevel = 100;
        $totalXp = 250;

        $level = (int) floor($totalXp / $xpPerLevel) + 1; // 3
        $xpProgress = $totalXp % $xpPerLevel; // 50
        $xpForNextLevel = $level * $xpPerLevel; // 300

        $this->assertEquals(3, $level);
        $this->assertEquals(50, $xpProgress);
        $this->assertEquals(300, $xpForNextLevel);
    }
}
