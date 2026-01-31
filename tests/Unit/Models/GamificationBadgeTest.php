<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GamificationBadge;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GamificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_badge_record(): void
    {
        $badge = GamificationBadge::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'badge_type' => 'first_sale',
            'badge_name' => 'First Sale',
            'earned_at' => now(),
        ]);

        $this->assertDatabaseHas('gamification_badges', [
            'user_id' => $this->testUser->id,
            'badge_type' => 'first_sale',
            'badge_name' => 'First Sale',
        ]);

        $this->assertEquals('first_sale', $badge->badge_type);
        $this->assertEquals('First Sale', $badge->badge_name);
    }

    /** @test */
    public function it_casts_earned_at_to_datetime(): void
    {
        $badge = GamificationBadge::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'badge_type' => 'first_sale',
            'badge_name' => 'First Sale',
            'earned_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $badge->earned_at);
    }

    /** @test */
    public function it_belongs_to_user(): void
    {
        $badge = GamificationBadge::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'badge_type' => 'first_sale',
            'badge_name' => 'First Sale',
            'earned_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $badge->user);
        $this->assertEquals($this->testUser->id, $badge->user->id);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $badge = GamificationBadge::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'badge_type' => 'first_sale',
            'badge_name' => 'First Sale',
            'earned_at' => now(),
        ]);

        $this->assertInstanceOf(Organization::class, $badge->organization);
        $this->assertEquals($this->testOrganization->id, $badge->organization->id);
    }

    /** @test */
    public function it_can_get_earned_badge_types_for_user(): void
    {
        // Create multiple badges
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

        $earnedTypes = GamificationBadge::where('user_id', $this->testUser->id)
            ->where('organization_id', $this->testOrganization->id)
            ->pluck('badge_type')
            ->toArray();

        $this->assertContains('first_sale', $earnedTypes);
        $this->assertContains('week_warrior', $earnedTypes);
        $this->assertCount(2, $earnedTypes);
    }

    /** @test */
    public function it_can_check_if_badge_is_earned(): void
    {
        GamificationBadge::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'badge_type' => 'first_sale',
            'badge_name' => 'First Sale',
            'earned_at' => now(),
        ]);

        $hasFirstSale = GamificationBadge::where('user_id', $this->testUser->id)
            ->where('organization_id', $this->testOrganization->id)
            ->where('badge_type', 'first_sale')
            ->exists();

        $hasSalesChampion = GamificationBadge::where('user_id', $this->testUser->id)
            ->where('organization_id', $this->testOrganization->id)
            ->where('badge_type', 'sales_champion')
            ->exists();

        $this->assertTrue($hasFirstSale);
        $this->assertFalse($hasSalesChampion);
    }

    /** @test */
    public function it_can_count_badges_by_category(): void
    {
        // Create badges
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
            'badge_type' => 'sales_starter',
            'badge_name' => 'Sales Starter',
            'earned_at' => now(),
        ]);

        $badgeCount = GamificationBadge::where('user_id', $this->testUser->id)
            ->where('organization_id', $this->testOrganization->id)
            ->count();

        $this->assertEquals(2, $badgeCount);
    }
}
