<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\TeamMember;
use App\Models\Department;

class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function team_member_model_can_be_created(): void
    {
        $member = TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'job_title' => 'Developer',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('team_members', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);
    }

    /** @test */
    public function team_member_belongs_to_organization(): void
    {
        $member = TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Test',
            'last_name' => 'Member',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->assertEquals($this->testOrganization->id, $member->organization_id);
    }

    /** @test */
    public function team_member_can_belong_to_department(): void
    {
        $department = Department::create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Engineering',
            'is_active' => true,
        ]);

        $member = TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'department_id' => $department->id,
            'first_name' => 'Engineer',
            'last_name' => 'Person',
            'email' => 'engineer@example.com',
            'is_active' => true,
        ]);

        $this->assertEquals($department->id, $member->department_id);
        $this->assertInstanceOf(Department::class, $member->department);
    }

    /** @test */
    public function team_member_can_be_updated(): void
    {
        $member = TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Original',
            'last_name' => 'Name',
            'email' => 'original@example.com',
            'is_active' => true,
        ]);

        $member->update([
            'first_name' => 'Updated',
            'email' => 'updated@example.com',
        ]);

        $this->assertDatabaseHas('team_members', [
            'id' => $member->id,
            'first_name' => 'Updated',
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function team_member_can_be_deactivated(): void
    {
        $member = TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Active',
            'last_name' => 'Employee',
            'email' => 'active@example.com',
            'is_active' => true,
        ]);

        $member->update(['is_active' => false]);

        $this->assertDatabaseHas('team_members', [
            'id' => $member->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function team_member_can_have_bank_details(): void
    {
        $member = TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Banked',
            'last_name' => 'Employee',
            'email' => 'banked@example.com',
            'bank_name' => 'Stanbic Bank',
            'bank_account_name' => 'Banked Employee',
            'bank_account_number' => '1234567890',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('team_members', [
            'email' => 'banked@example.com',
            'bank_name' => 'Stanbic Bank',
            'bank_account_number' => '1234567890',
        ]);
    }

    /** @test */
    public function team_member_full_name_attribute_works(): void
    {
        $member = TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'is_active' => true,
        ]);

        $this->assertEquals('John Doe', $member->full_name);
    }

    /** @test */
    public function team_members_can_be_filtered_by_organization(): void
    {
        // Create member for test org
        TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Test',
            'last_name' => 'Org',
            'email' => 'testorg@example.com',
            'is_active' => true,
        ]);

        // Create another org and member
        $otherOrg = \App\Models\Organization::create([
            'name' => 'Other',
            'slug' => 'other-org-test',
        ]);

        TeamMember::create([
            'organization_id' => $otherOrg->id,
            'first_name' => 'Other',
            'last_name' => 'Org',
            'email' => 'otherorg@example.com',
            'is_active' => true,
        ]);

        $testOrgMembers = TeamMember::where('organization_id', $this->testOrganization->id)->get();
        $otherOrgMembers = TeamMember::where('organization_id', $otherOrg->id)->get();

        $this->assertCount(1, $testOrgMembers);
        $this->assertCount(1, $otherOrgMembers);
        $this->assertEquals('testorg@example.com', $testOrgMembers->first()->email);
    }

    /** @test */
    public function team_members_can_be_filtered_by_department(): void
    {
        $dept1 = Department::create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Sales',
            'is_active' => true,
        ]);

        $dept2 = Department::create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Marketing',
            'is_active' => true,
        ]);

        TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'department_id' => $dept1->id,
            'first_name' => 'Sales',
            'last_name' => 'Person',
            'email' => 'sales@example.com',
            'is_active' => true,
        ]);

        TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'department_id' => $dept2->id,
            'first_name' => 'Marketing',
            'last_name' => 'Person',
            'email' => 'marketing@example.com',
            'is_active' => true,
        ]);

        $salesMembers = TeamMember::where('department_id', $dept1->id)->get();

        $this->assertCount(1, $salesMembers);
        $this->assertEquals('sales@example.com', $salesMembers->first()->email);
    }

    /** @test */
    public function team_members_can_be_filtered_by_active_status(): void
    {
        TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Active',
            'last_name' => 'Employee',
            'email' => 'active@example.com',
            'is_active' => true,
        ]);

        TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Inactive',
            'last_name' => 'Employee',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $activeMembers = TeamMember::where('organization_id', $this->testOrganization->id)
            ->where('is_active', true)
            ->get();

        $this->assertCount(1, $activeMembers);
        $this->assertEquals('active@example.com', $activeMembers->first()->email);
    }

    /** @test */
    public function team_members_can_be_searched_by_name(): void
    {
        TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Findable',
            'last_name' => 'Person',
            'email' => 'findable@example.com',
            'is_active' => true,
        ]);

        TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Other',
            'last_name' => 'Person',
            'email' => 'other@example.com',
            'is_active' => true,
        ]);

        $results = TeamMember::where('organization_id', $this->testOrganization->id)
            ->where(function ($q) {
                $q->where('first_name', 'like', '%Findable%')
                  ->orWhere('last_name', 'like', '%Findable%');
            })
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Findable', $results->first()->first_name);
    }

    /** @test */
    public function team_member_can_be_linked_to_user(): void
    {
        $member = TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
            'first_name' => 'Linked',
            'last_name' => 'User',
            'email' => $this->testUser->email,
            'is_active' => true,
        ]);

        $this->assertEquals($this->testUser->id, $member->user_id);
        $this->assertInstanceOf(\App\Models\User::class, $member->user);
    }

    /** @test */
    public function team_member_salary_is_cast_to_decimal(): void
    {
        $member = TeamMember::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Paid',
            'last_name' => 'Employee',
            'email' => 'paid@example.com',
            'salary' => 5000.50,
            'is_active' => true,
        ]);

        $this->assertEquals('5000.50', $member->salary);
    }
}
