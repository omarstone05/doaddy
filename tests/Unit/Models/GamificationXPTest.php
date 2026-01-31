<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GamificationXP;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GamificationXPTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_xp_record(): void
    {
        $xp = GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 50,
            'reason' => 'sale_recorded',
            'context' => ['sale_id' => 'test-123'],
        ]);

        $this->assertDatabaseHas('gamification_xp', [
            'user_id' => $this->testUser->id,
            'xp_amount' => 50,
            'reason' => 'sale_recorded',
        ]);

        $this->assertEquals(50, $xp->xp_amount);
        $this->assertEquals('sale_recorded', $xp->reason);
    }

    /** @test */
    public function it_casts_xp_amount_to_integer(): void
    {
        $xp = GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 25,
            'reason' => 'test',
        ]);

        $this->assertIsInt($xp->xp_amount);
    }

    /** @test */
    public function it_casts_context_to_array(): void
    {
        $xp = GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 10,
            'reason' => 'test',
            'context' => ['key' => 'value', 'nested' => ['a' => 1]],
        ]);

        $this->assertIsArray($xp->context);
        $this->assertEquals('value', $xp->context['key']);
        $this->assertEquals(1, $xp->context['nested']['a']);
    }

    /** @test */
    public function it_belongs_to_user(): void
    {
        $xp = GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 10,
            'reason' => 'test',
        ]);

        $this->assertInstanceOf(User::class, $xp->user);
        $this->assertEquals($this->testUser->id, $xp->user->id);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $xp = GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 10,
            'reason' => 'test',
        ]);

        $this->assertInstanceOf(Organization::class, $xp->organization);
        $this->assertEquals($this->testOrganization->id, $xp->organization->id);
    }

    /** @test */
    public function it_can_calculate_total_xp_for_user(): void
    {
        // Create multiple XP records
        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 50,
            'reason' => 'sale_recorded',
        ]);

        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 30,
            'reason' => 'invoice_issued',
        ]);

        GamificationXP::create([
            'user_id' => $this->testUser->id,
            'organization_id' => $this->testOrganization->id,
            'xp_amount' => 20,
            'reason' => 'customer_added',
        ]);

        $totalXP = GamificationXP::where('user_id', $this->testUser->id)
            ->where('organization_id', $this->testOrganization->id)
            ->sum('xp_amount');

        $this->assertEquals(100, $totalXP);
    }

    /** @test */
    public function it_can_get_recent_xp_records(): void
    {
        // Create XP records
        for ($i = 0; $i < 5; $i++) {
            GamificationXP::create([
                'user_id' => $this->testUser->id,
                'organization_id' => $this->testOrganization->id,
                'xp_amount' => 10,
                'reason' => "action_{$i}",
            ]);
        }

        $recentXP = GamificationXP::where('user_id', $this->testUser->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $this->assertCount(3, $recentXP);
    }
}
