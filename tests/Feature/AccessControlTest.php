<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Mail\OrganizationInvitationMail;
use App\Mail\AddedToOrganizationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected User $member;
    protected Organization $organization;
    protected OrganizationRole $ownerRole;
    protected OrganizationRole $adminRole;
    protected OrganizationRole $memberRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'OrganizationRoleSeeder']);
        
        // Get roles
        $this->ownerRole = OrganizationRole::where('slug', 'owner')->first();
        $this->adminRole = OrganizationRole::where('slug', 'admin')->first();
        $this->memberRole = OrganizationRole::where('slug', 'member')->first();
        
        // Create organization and users
        $this->organization = Organization::factory()->create();
        
        $this->owner = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        
        $this->admin = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        
        $this->member = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        
        // Attach users to organization with roles
        $this->organization->members()->attach($this->owner->id, [
            'role_id' => $this->ownerRole->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        
        $this->organization->members()->attach($this->admin->id, [
            'role_id' => $this->adminRole->id,
            'role' => 'admin',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        
        $this->organization->members()->attach($this->member->id, [
            'role_id' => $this->memberRole->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        Mail::fake();
    }

    /** @test */
    public function owner_can_invite_new_user()
    {
        $this->actingAs($this->owner);

        $response = $this->post('/settings/team/invite', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'role_id' => $this->memberRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('organization_invitations', [
            'email' => 'newuser@example.com',
            'organization_id' => $this->organization->id,
            'role_id' => $this->memberRole->id,
        ]);
        
        Mail::assertSent(OrganizationInvitationMail::class, function ($mail) {
            return $mail->hasTo('newuser@example.com');
        });
    }

    /** @test */
    public function admin_can_invite_user_with_lower_role()
    {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/team/invite', [
            'email' => 'newmember@example.com',
            'role_id' => $this->memberRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('organization_invitations', [
            'email' => 'newmember@example.com',
        ]);
    }

    /** @test */
    public function admin_cannot_invite_user_with_owner_role()
    {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/team/invite', [
            'email' => 'newowner@example.com',
            'role_id' => $this->ownerRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        
        $this->assertDatabaseMissing('organization_invitations', [
            'email' => 'newowner@example.com',
        ]);
    }

    /** @test */
    public function member_cannot_invite_users()
    {
        $this->actingAs($this->member);

        $response = $this->post('/settings/team/invite', [
            'email' => 'someone@example.com',
            'role_id' => $this->memberRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    /** @test */
    public function inviting_existing_user_adds_them_to_organization()
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->actingAs($this->owner);

        $response = $this->post('/settings/team/invite', [
            'email' => 'existing@example.com',
            'role_id' => $this->memberRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertTrue($existingUser->belongsToOrganization($this->organization->id));
        
        Mail::assertSent(AddedToOrganizationMail::class, function ($mail) use ($existingUser) {
            return $mail->hasTo($existingUser->email);
        });
    }

    /** @test */
    public function cannot_invite_user_already_in_organization()
    {
        $this->actingAs($this->owner);

        $response = $this->post('/settings/team/invite', [
            'email' => $this->member->email,
            'role_id' => $this->adminRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    /** @test */
    public function owner_can_change_user_role()
    {
        $this->actingAs($this->owner);

        $response = $this->put("/settings/team/{$this->member->id}/role", [
            'role_id' => $this->adminRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->member->refresh();
        $this->assertEquals('admin', $this->member->getRoleInOrganization($this->organization->id));
    }

    /** @test */
    public function admin_can_change_member_role()
    {
        $this->actingAs($this->admin);

        $viewerRole = OrganizationRole::where('slug', 'viewer')->first();

        $response = $this->put("/settings/team/{$this->member->id}/role", [
            'role_id' => $viewerRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admin_cannot_change_owner_role()
    {
        $this->actingAs($this->admin);

        $response = $this->put("/settings/team/{$this->owner->id}/role", [
            'role_id' => $this->memberRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    /** @test */
    public function user_cannot_change_own_role()
    {
        $this->actingAs($this->admin);

        $response = $this->put("/settings/team/{$this->admin->id}/role", [
            'role_id' => $this->ownerRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    /** @test */
    public function owner_can_remove_user_from_organization()
    {
        $this->actingAs($this->owner);

        $response = $this->delete("/settings/team/{$this->member->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertFalse($this->member->fresh()->belongsToOrganization($this->organization->id));
    }

    /** @test */
    public function admin_can_remove_member()
    {
        $this->actingAs($this->admin);

        $response = $this->delete("/settings/team/{$this->member->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admin_cannot_remove_owner()
    {
        $this->actingAs($this->admin);

        $response = $this->delete("/settings/team/{$this->owner->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    /** @test */
    public function user_cannot_remove_self()
    {
        $this->actingAs($this->admin);

        $response = $this->delete("/settings/team/{$this->admin->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    /** @test */
    public function cannot_remove_last_owner()
    {
        $this->actingAs($this->owner);

        $response = $this->delete("/settings/team/{$this->owner->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    /** @test */
    public function owner_can_toggle_user_status()
    {
        $this->actingAs($this->owner);

        $response = $this->post("/settings/team/{$this->member->id}/toggle-status");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Check status was toggled (DB returns 0/1, so compare as integers)
        $pivot = $this->member->organizations()
            ->where('organizations.id', $this->organization->id)
            ->first()
            ->pivot;
        
        $this->assertEquals(0, (int) $pivot->is_active);
    }

    /** @test */
    public function member_cannot_toggle_user_status()
    {
        $this->actingAs($this->member);

        $response = $this->post("/settings/team/{$this->admin->id}/toggle-status");

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    /** @test */
    public function invitation_email_contains_correct_data()
    {
        // Use fresh Mail::fake() to ensure clean state
        Mail::fake();
        
        $this->actingAs($this->owner);

        $response = $this->post('/settings/team/invite', [
            'email' => 'testdata@example.com',
            'name' => 'Test User',
            'role_id' => $this->memberRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(OrganizationInvitationMail::class, function ($mail) {
            return $mail->hasTo('testdata@example.com') &&
                   $mail->inviteeName === 'Test User' &&
                   $mail->role->slug === 'member';
        });
    }

    /** @test */
    public function added_to_organization_email_contains_correct_data()
    {
        $existingUser = User::factory()->create();

        $this->actingAs($this->owner);

        $this->post('/settings/team/invite', [
            'email' => $existingUser->email,
            'role_id' => $this->adminRole->id,
        ]);

        Mail::assertSent(AddedToOrganizationMail::class, function ($mail) {
            $this->assertEquals($this->organization->name, $mail->organization->name);
            $this->assertEquals('admin', $mail->role->slug);
            $this->assertEquals($this->owner->name, $mail->addedBy->name);
            return true;
        });
    }

    /** @test */
    public function invite_requires_valid_email()
    {
        $this->actingAs($this->owner);

        $response = $this->post('/settings/team/invite', [
            'email' => 'not-an-email',
            'role_id' => $this->memberRole->id,
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function invite_requires_valid_role_id()
    {
        $this->actingAs($this->owner);

        $response = $this->post('/settings/team/invite', [
            'email' => 'test@example.com',
            'role_id' => 99999,
        ]);

        $response->assertSessionHasErrors('role_id');
    }
}
