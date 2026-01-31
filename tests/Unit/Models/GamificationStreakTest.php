<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GamificationStreak;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GamificationStreakTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_streak_record(): void
    {
        $streak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 5,
            'longest_streak' => 10,
            'last_activity_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('gamification_streaks', [
            'user_id' => $this->testUser->id,
            'streak_type' => 'daily',
            'current_streak' => 5,
            'longest_streak' => 10,
        ]);
    }

    /** @test */
    public function it_tracks_current_and_longest_streak(): void
    {
        $streak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 7,
            'longest_streak' => 14,
            'last_activity_date' => now()->toDateString(),
        ]);

        $this->assertEquals(7, $streak->current_streak);
        $this->assertEquals(14, $streak->longest_streak);
    }

    /** @test */
    public function it_can_increment_streak(): void
    {
        $streak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 5,
            'longest_streak' => 5,
            'last_activity_date' => now()->subDay()->toDateString(),
        ]);

        $streak->current_streak += 1;
        if ($streak->current_streak > $streak->longest_streak) {
            $streak->longest_streak = $streak->current_streak;
        }
        $streak->last_activity_date = now()->toDateString();
        $streak->save();

        $streak->refresh();

        $this->assertEquals(6, $streak->current_streak);
        $this->assertEquals(6, $streak->longest_streak);
    }

    /** @test */
    public function it_preserves_longest_streak_on_reset(): void
    {
        $streak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 5,
            'longest_streak' => 20,
            'last_activity_date' => now()->subDays(3)->toDateString(),
        ]);

        // Reset current streak
        $streak->current_streak = 1;
        $streak->last_activity_date = now()->toDateString();
        $streak->save();

        $streak->refresh();

        $this->assertEquals(1, $streak->current_streak);
        $this->assertEquals(20, $streak->longest_streak); // Should remain unchanged
    }

    /** @test */
    public function it_belongs_to_user(): void
    {
        $streak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 1,
            'longest_streak' => 1,
            'last_activity_date' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(User::class, $streak->user);
        $this->assertEquals($this->testUser->id, $streak->user->id);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $streak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 1,
            'longest_streak' => 1,
            'last_activity_date' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(Organization::class, $streak->organization);
        $this->assertEquals($this->testOrganization->id, $streak->organization->id);
    }

    /** @test */
    public function it_can_determine_if_streak_is_active(): void
    {
        // Active streak (activity today)
        $activeStreak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 5,
            'longest_streak' => 5,
            'last_activity_date' => now()->toDateString(),
        ]);

        $isActive = $activeStreak->last_activity_date >= now()->subDay()->toDateString();
        $this->assertTrue($isActive);
    }

    /** @test */
    public function it_can_determine_if_streak_is_broken(): void
    {
        // Broken streak (no activity for 2+ days)
        $brokenStreak = GamificationStreak::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'streak_type' => 'daily',
            'current_streak' => 10,
            'longest_streak' => 10,
            'last_activity_date' => now()->subDays(3)->toDateString(),
        ]);

        $isBroken = $brokenStreak->last_activity_date < now()->subDay()->toDateString();
        $this->assertTrue($isBroken);
    }
}
