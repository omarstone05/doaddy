<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Addy\GamificationService;
use App\Models\GamificationXP;
use App\Models\GamificationBadge;
use App\Models\GamificationStreak;
use App\Models\Notification;
use Illuminate\Support\Facades\Config;

class GamificationServiceTest extends TestCase
{
    protected GamificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure gamification settings for testing
        Config::set('features.gamification', true);
        Config::set('gamification.xp_rewards', [
            'sale_recorded' => 10,
            'invoice_issued' => 15,
            'customer_added' => 5,
            'expense_recorded' => 5,
            'daily_streak_7' => 50,
            'daily_streak_14' => 100,
            'daily_streak_30' => 200,
            'badge_unlocked_bonus' => 25,
        ]);
        Config::set('gamification.badges', [
            'first_sale' => [
                'name' => 'First Sale',
                'description' => 'Made your first sale',
                'icon' => '🎉',
                'category' => 'sales',
                'metric' => 'sales_count',
                'threshold' => 1,
            ],
            'sales_10' => [
                'name' => 'Rising Seller',
                'description' => 'Made 10 sales',
                'icon' => '📈',
                'category' => 'sales',
                'metric' => 'sales_count',
                'threshold' => 10,
            ],
        ]);
        Config::set('gamification.xp_per_level', 100);
        Config::set('gamification.level_titles', [
            1 => 'Emerging Business',
            5 => 'Growing Business',
            10 => 'Established Business',
        ]);

        $this->service = new GamificationService();
    }

    /** @test */
    public function it_awards_xp_for_valid_action(): void
    {
        $xpAwarded = $this->service->awardXP(
            $this->testUser->id,
            'sale_recorded',
            $this->testOrganization->id
        );

        $this->assertEquals(10, $xpAwarded);

        // Verify XP was recorded in database
        $this->assertDatabaseHas('gamification_xp', [
            'user_id' => $this->testUser->id,
            'xp_amount' => 10,
            'reason' => 'sale_recorded',
        ]);
    }

    /** @test */
    public function it_returns_zero_for_unknown_action(): void
    {
        $xpAwarded = $this->service->awardXP(
            $this->testUser->id,
            'unknown_action',
            $this->testOrganization->id
        );

        $this->assertEquals(0, $xpAwarded);
    }

    /** @test */
    public function it_does_not_award_xp_when_gamification_disabled(): void
    {
        Config::set('features.gamification', false);
        $service = new GamificationService();

        $xpAwarded = $service->awardXP(
            $this->testUser->id,
            'sale_recorded',
            $this->testOrganization->id
        );

        $this->assertEquals(0, $xpAwarded);
    }

    /** @test */
    public function it_creates_xp_notification(): void
    {
        $this->service->awardXP(
            $this->testUser->id,
            'sale_recorded',
            $this->testOrganization->id
        );

        // Check that notification was created
        $notification = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'gamification_xp')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('10 XP', $notification->title);
    }

    /** @test */
    public function it_starts_streak_on_first_activity(): void
    {
        $this->service->updateStreak($this->testUser->id, $this->testOrganization->id);

        $streak = GamificationStreak::where('user_id', $this->testUser->id)
            ->where('streak_type', 'daily')
            ->first();

        $this->assertNotNull($streak);
        $this->assertEquals(1, $streak->current_streak);
        $this->assertEquals(1, $streak->longest_streak);
    }

    /** @test */
    public function it_does_not_increment_streak_for_same_day(): void
    {
        // First activity
        $this->service->updateStreak($this->testUser->id, $this->testOrganization->id);

        // Second activity same day
        $this->service->updateStreak($this->testUser->id, $this->testOrganization->id);

        $streak = GamificationStreak::where('user_id', $this->testUser->id)
            ->where('streak_type', 'daily')
            ->first();

        $this->assertEquals(1, $streak->current_streak);
    }

    /** @test */
    public function it_increments_streak_for_consecutive_days(): void
    {
        // Create streak with yesterday's date
        $streak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 5,
            'longest_streak' => 5,
            'last_activity_date' => now()->subDay(),
        ]);

        $this->service->updateStreak($this->testUser->id, $this->testOrganization->id);

        $streak->refresh();
        $this->assertEquals(6, $streak->current_streak);
        $this->assertEquals(6, $streak->longest_streak);
    }

    /** @test */
    public function it_resets_streak_after_missed_day(): void
    {
        // Create streak with 3 days ago
        $streak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 10,
            'longest_streak' => 15,
            'last_activity_date' => now()->subDays(3),
        ]);

        $this->service->updateStreak($this->testUser->id, $this->testOrganization->id);

        $streak->refresh();
        $this->assertEquals(1, $streak->current_streak);
        $this->assertEquals(15, $streak->longest_streak); // Longest should remain
    }

    /** @test */
    public function it_gets_user_stats_correctly(): void
    {
        // Award some XP
        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 150,
            'reason' => 'test',
        ]);

        // Create a badge
        GamificationBadge::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'badge_type' => 'first_sale',
            'badge_name' => 'First Sale',
            'earned_at' => now(),
        ]);

        // Create a streak
        GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 7,
            'longest_streak' => 14,
            'last_activity_date' => now(),
        ]);

        $stats = $this->service->getUserStats($this->testUser->id);

        $this->assertEquals(150, $stats['total_xp']);
        $this->assertEquals(2, $stats['level']); // 150 XP / 100 XP per level = level 2
        $this->assertEquals(50, $stats['xp_in_current_level']); // 150 % 100 = 50
        $this->assertEquals(50, $stats['xp_for_next_level']); // 100 - 50 = 50
        $this->assertEquals(1, $stats['badges_count']);
        $this->assertEquals(7, $stats['current_streak']);
        $this->assertEquals(14, $stats['longest_streak']);
    }

    /** @test */
    public function it_calculates_level_correctly(): void
    {
        // 0 XP = Level 1
        $stats = $this->service->getUserStats($this->testUser->id);
        $this->assertEquals(1, $stats['level']);

        // 50 XP = Level 1
        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'xp_amount' => 50,
            'reason' => 'test',
        ]);
        $stats = $this->service->getUserStats($this->testUser->id);
        $this->assertEquals(1, $stats['level']);

        // 100 XP = Level 2
        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'xp_amount' => 50,
            'reason' => 'test',
        ]);
        $stats = $this->service->getUserStats($this->testUser->id);
        $this->assertEquals(2, $stats['level']);

        // 250 XP = Level 3
        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'xp_amount' => 150,
            'reason' => 'test',
        ]);
        $stats = $this->service->getUserStats($this->testUser->id);
        $this->assertEquals(3, $stats['level']);
    }

    /** @test */
    public function it_gets_all_badges_with_status(): void
    {
        // Unlock one badge
        GamificationBadge::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'badge_type' => 'first_sale',
            'badge_name' => 'First Sale',
            'earned_at' => now(),
        ]);

        $badges = $this->service->getAllBadgesWithStatus($this->testUser->id);

        $this->assertCount(2, $badges); // first_sale and sales_10

        $firstSaleBadge = collect($badges)->firstWhere('type', 'first_sale');
        $sales10Badge = collect($badges)->firstWhere('type', 'sales_10');

        $this->assertTrue($firstSaleBadge['unlocked']);
        $this->assertFalse($sales10Badge['unlocked']);
    }

    /** @test */
    public function it_stores_context_with_xp(): void
    {
        $context = ['invoice_id' => 'test-123', 'amount' => 1000];

        $this->service->awardXP(
            $this->testUser->id,
            'invoice_issued',
            $this->testOrganization->id,
            $context
        );

        $xp = GamificationXP::where('user_id', $this->testUser->id)
            ->where('reason', 'invoice_issued')
            ->first();

        $this->assertNotNull($xp);
        $this->assertEquals('test-123', $xp->context['invoice_id']);
        $this->assertEquals(1000, $xp->context['amount']);
    }
}
