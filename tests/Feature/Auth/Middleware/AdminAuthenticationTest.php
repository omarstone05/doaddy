<?php

namespace Tests\Feature\Auth\Middleware;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
        ]);
    }

    /**
     * Test unauthenticated user is redirected to login
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test organization owner can access admin routes
     */
    public function test_organization_owner_can_access_admin_routes(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
            'organization_id' => $this->organization->id,
        ]);

        $owner->organizations()->attach($this->organization->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get('/admin/dashboard');

        // Should not be forbidden (may return 200 or redirect depending on view)
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /**
     * Test regular member cannot access admin routes
     */
    public function test_member_cannot_access_admin_routes(): void
    {
        $member = User::factory()->create([
            'is_active' => true,
            'organization_id' => $this->organization->id,
        ]);

        $member->organizations()->attach($this->organization->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($member)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Test viewer cannot access admin routes
     */
    public function test_viewer_cannot_access_admin_routes(): void
    {
        $viewer = User::factory()->create([
            'is_active' => true,
            'organization_id' => $this->organization->id,
        ]);

        $viewer->organizations()->attach($this->organization->id, [
            'role' => 'viewer',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($viewer)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Test user without organization context gets 403
     */
    public function test_user_without_organization_context_gets_403(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'organization_id' => null,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Test admin middleware on various admin routes
     */
    public function test_admin_middleware_on_system_settings(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
            'organization_id' => $this->organization->id,
        ]);

        $owner->organizations()->attach($this->organization->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get('/admin/system-settings');

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /**
     * Test owner of different org cannot access another org's admin
     */
    public function test_owner_of_different_org_cannot_access_admin(): void
    {
        $otherOrg = Organization::factory()->create();

        $owner = User::factory()->create([
            'is_active' => true,
            'organization_id' => $otherOrg->id,
        ]);

        // Owner of other org
        $owner->organizations()->attach($otherOrg->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Member (not owner) of this org
        $owner->organizations()->attach($this->organization->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Try to access admin with this org's context (where they're just a member)
        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get('/admin/dashboard');

        $response->assertStatus(403);
    }
}
