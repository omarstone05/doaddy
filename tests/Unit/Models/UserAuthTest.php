<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test belongsToOrganization returns true for attached org
     */
    public function test_belongs_to_organization_returns_true_for_attached_org(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();

        $user->organizations()->attach($org->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertTrue($user->belongsToOrganization($org->id));
    }

    /**
     * Test belongsToOrganization returns false for non-attached org
     */
    public function test_belongs_to_organization_returns_false_for_non_attached_org(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($user->belongsToOrganization($org->id));
    }

    /**
     * Test getRoleInOrganization returns correct role
     */
    public function test_get_role_in_organization_returns_correct_role(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();

        $user->organizations()->attach($org->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertEquals('owner', $user->getRoleInOrganization($org->id));
    }

    /**
     * Test getRoleInOrganization returns null for non-member
     */
    public function test_get_role_in_organization_returns_null_for_non_member(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertNull($user->getRoleInOrganization($org->id));
    }

    /**
     * Test isOwnerOf returns true for owner
     */
    public function test_is_owner_of_returns_true_for_owner(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();

        $user->organizations()->attach($org->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertTrue($user->isOwnerOf($org->id));
    }

    /**
     * Test isOwnerOf returns false for non-owner
     */
    public function test_is_owner_of_returns_false_for_non_owner(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();

        $user->organizations()->attach($org->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertFalse($user->isOwnerOf($org->id));
    }

    /**
     * Test isSuperAdmin returns true for super admin
     */
    public function test_is_super_admin_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->assertTrue($user->isSuperAdmin());
    }

    /**
     * Test isSuperAdmin returns false for regular user
     */
    public function test_is_super_admin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $this->assertFalse($user->isSuperAdmin());
    }

    /**
     * Test organization attribute returns current organization
     */
    public function test_organization_attribute_returns_current_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
        ]);

        $user->organizations()->attach($org->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertEquals($org->id, $user->organization?->id);
    }

    /**
     * Test current_organization_id accessor
     */
    public function test_current_organization_id_accessor(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
        ]);

        $user->organizations()->attach($org->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertEquals($org->id, $user->current_organization_id);
    }

    /**
     * Test user can have multiple organizations
     */
    public function test_user_can_have_multiple_organizations(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $user = User::factory()->create();

        $user->organizations()->attach($org1->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $user->organizations()->attach($org2->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now()->subDay(),
        ]);

        $this->assertEquals(2, $user->organizations()->count());
        $this->assertTrue($user->belongsToOrganization($org1->id));
        $this->assertTrue($user->belongsToOrganization($org2->id));
    }

    /**
     * Test user roles differ across organizations
     */
    public function test_user_roles_differ_across_organizations(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $user = User::factory()->create();

        $user->organizations()->attach($org1->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $user->organizations()->attach($org2->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertEquals('owner', $user->getRoleInOrganization($org1->id));
        $this->assertEquals('member', $user->getRoleInOrganization($org2->id));
        $this->assertTrue($user->isOwnerOf($org1->id));
        $this->assertFalse($user->isOwnerOf($org2->id));
    }

    /**
     * Test isAdmin returns true for admin users
     */
    public function test_is_admin_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->assertTrue($user->isAdmin());
    }

    /**
     * Test isAdmin returns false for regular users
     */
    public function test_is_admin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Without admin roles attached
        $this->assertFalse($user->isAdmin());
    }

    /**
     * Test organization relationship ordering
     */
    public function test_organizations_ordered_by_joined_at_desc(): void
    {
        $user = User::factory()->create();
        
        $org1 = Organization::factory()->create(['name' => 'First Org']);
        $org2 = Organization::factory()->create(['name' => 'Second Org']);

        $user->organizations()->attach($org1->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now()->subDays(5),
        ]);

        $user->organizations()->attach($org2->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Most recently joined should be first
        $this->assertEquals($org2->id, $user->organizations()->first()->id);
    }
}
