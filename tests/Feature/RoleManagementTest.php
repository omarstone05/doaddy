<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'OrganizationRoleSeeder']);
    }

    /** @test */
    public function default_roles_are_seeded()
    {
        $roles = OrganizationRole::all();
        
        $this->assertGreaterThanOrEqual(5, $roles->count());
        $this->assertNotNull(OrganizationRole::where('slug', 'owner')->first());
        $this->assertNotNull(OrganizationRole::where('slug', 'admin')->first());
        $this->assertNotNull(OrganizationRole::where('slug', 'member')->first());
    }

    /** @test */
    public function can_assign_role_to_user()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        
        // Attach user to organization first
        $org->members()->attach($user->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $result = $org->assignRoleToUser($user, 'admin');
        
        $this->assertTrue($result);
        $this->assertEquals('admin', $user->getRoleInOrganization($org->id));
    }

    /** @test */
    public function can_change_user_role()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        
        // Attach user with member role
        $memberRole = OrganizationRole::where('slug', 'member')->first();
        $org->members()->attach($user->id, [
            'role' => 'member',
            'role_id' => $memberRole->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $result = $org->changeUserRole($user, 'admin');
        
        $this->assertTrue($result);
        $this->assertEquals('admin', $user->getRoleInOrganization($org->id));
    }

    /** @test */
    public function can_get_user_organization_role()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $adminRole = OrganizationRole::where('slug', 'admin')->first();
        
        // Use syncWithoutDetaching to properly set role_id
        $org->members()->syncWithoutDetaching([
            $user->id => [
                'role' => 'admin',
                'role_id' => $adminRole->id,
                'is_active' => true,
                'joined_at' => now(),
            ]
        ]);

        // Refresh the relationship
        $user->refresh();
        $role = $user->getOrganizationRole($org->id);
        
        $this->assertNotNull($role);
        $this->assertEquals('admin', $role->slug);
        $this->assertEquals('Admin', $role->name);
    }

    /** @test */
    public function permission_check_works_correctly()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $adminRole = OrganizationRole::where('slug', 'admin')->first();
        
        $org->members()->syncWithoutDetaching([
            $user->id => [
                'role' => 'admin',
                'role_id' => $adminRole->id,
                'is_active' => true,
                'joined_at' => now(),
            ]
        ]);

        $user->refresh();
        
        // Admin should have invoices.create but not organization.delete
        $this->assertTrue($user->hasPermissionInOrganization($org->id, 'invoices.create'));
        $this->assertFalse($user->hasPermissionInOrganization($org->id, 'organization.delete'));
    }

    /** @test */
    public function owner_has_all_permissions()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $ownerRole = OrganizationRole::where('slug', 'owner')->first();
        
        $org->members()->syncWithoutDetaching([
            $user->id => [
                'role' => 'owner',
                'role_id' => $ownerRole->id,
                'is_active' => true,
                'joined_at' => now(),
            ]
        ]);

        $user->refresh();
        
        $this->assertTrue($user->hasPermissionInOrganization($org->id, 'organization.delete'));
        $this->assertTrue($user->hasPermissionInOrganization($org->id, 'invoices.create'));
        $this->assertTrue($user->hasPermissionInOrganization($org->id, 'users.manage'));
    }

    /** @test */
    public function can_create_custom_role()
    {
        $role = OrganizationRole::create([
            'name' => 'Accountant',
            'slug' => 'accountant',
            'description' => 'Financial management',
            'level' => 50,
            'is_system' => false,
            'permissions' => [
                'money.view',
                'money.create',
                'invoices.view',
            ],
        ]);

        $this->assertDatabaseHas('organization_roles', [
            'slug' => 'accountant',
            'is_system' => false,
        ]);
        
        $this->assertTrue($role->hasPermission('money.view'));
        $this->assertFalse($role->hasPermission('organization.delete'));
    }

    /** @test */
    public function role_hierarchy_works()
    {
        $owner = OrganizationRole::where('slug', 'owner')->first();
        $admin = OrganizationRole::where('slug', 'admin')->first();
        $member = OrganizationRole::where('slug', 'member')->first();

        $this->assertTrue($owner->isHigherThan($admin));
        $this->assertTrue($admin->isHigherThan($member));
        $this->assertFalse($member->isHigherThan($owner));
    }

    /** @test */
    public function role_can_manage_works()
    {
        $owner = OrganizationRole::where('slug', 'owner')->first();
        $admin = OrganizationRole::where('slug', 'admin')->first();
        $member = OrganizationRole::where('slug', 'member')->first();

        $this->assertTrue($owner->canManage($admin));
        $this->assertTrue($admin->canManage($member));
        $this->assertFalse($member->canManage($admin));
    }

    /** @test */
    public function user_can_have_different_roles_in_different_orgs()
    {
        $user = User::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        
        $ownerRole = OrganizationRole::where('slug', 'owner')->first();
        $memberRole = OrganizationRole::where('slug', 'member')->first();
        
        $org1->members()->attach($user->id, [
            'role' => 'owner',
            'role_id' => $ownerRole->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);
        
        $org2->members()->attach($user->id, [
            'role' => 'member',
            'role_id' => $memberRole->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertEquals('owner', $user->getRoleInOrganization($org1->id));
        $this->assertEquals('member', $user->getRoleInOrganization($org2->id));
    }

    /** @test */
    public function returns_null_for_user_without_role()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();

        $this->assertNull($user->getRoleInOrganization($org->id));
        $this->assertNull($user->getOrganizationRole($org->id));
    }

    /** @test */
    public function returns_false_for_invalid_role_slug()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        
        $org->members()->attach($user->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $result = $org->assignRoleToUser($user, 'invalid-role');
        
        $this->assertFalse($result);
    }
}

