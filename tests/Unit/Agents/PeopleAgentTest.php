<?php

namespace Tests\Unit\Agents;

use Tests\TestCase;
use App\Services\Addy\Agents\PeopleAgent;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\TeamMember;
use App\Models\LeaveType;

class PeopleAgentTest extends TestCase
{
    protected PeopleAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new PeopleAgent($this->testOrganization);
    }

    /** @test */
    public function it_perceives_team_stats_correctly(): void
    {
        User::factory()->count(8)->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $perception = $this->agent->perceive();

        // Should include test user + 8 new users = 9 total
        $this->assertGreaterThanOrEqual(9, $perception['team_stats']['total']);
    }

    /** @test */
    public function it_tracks_pending_leave_requests(): void
    {
        $teamMember = TeamMember::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Create a leave type first (required by foreign key)
        $leaveType = LeaveType::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $this->testOrganization->id,
            'name' => 'Annual Leave',
            'maximum_days_per_year' => 20,
            'is_active' => true,
        ]);

        // Create leave requests with explicit dates to avoid factory date issues
        for ($i = 0; $i < 3; $i++) {
            $startDate = now()->addDays($i + 1);
            $endDate = now()->addDays($i + 3);
            LeaveRequest::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'organization_id' => $this->testOrganization->id,
                'team_member_id' => $teamMember->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'number_of_days' => $startDate->diffInDays($endDate),
                'reason' => 'Test leave request',
                'status' => 'pending',
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            $startDate = now()->addDays($i + 10);
            $endDate = now()->addDays($i + 12);
            LeaveRequest::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'organization_id' => $this->testOrganization->id,
                'team_member_id' => $teamMember->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'number_of_days' => $startDate->diffInDays($endDate),
                'reason' => 'Test approved leave',
                'status' => 'approved',
            ]);
        }

        $perception = $this->agent->perceive();

        $this->assertEquals(3, $perception['leave_patterns']['pending_requests']);
    }

    /** @test */
    public function it_only_perceives_own_organization_data(): void
    {
        $otherOrg = $this->createOtherOrganization();

        // Create user in other org
        User::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        // Create user in test org
        User::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $perception = $this->agent->perceive();

        // Should only count test org's users (test user + 1 new = 2 minimum)
        $this->assertLessThanOrEqual(2, $perception['team_stats']['total']);
    }
}

