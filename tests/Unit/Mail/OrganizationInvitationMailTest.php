<?php

namespace Tests\Unit\Mail;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Mail\OrganizationInvitationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrganizationInvitationMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'OrganizationRoleSeeder']);
    }

    /** @test */
    public function it_sets_correct_subject()
    {
        $organization = Organization::factory()->create(['name' => 'Test Company']);
        $role = OrganizationRole::where('slug', 'member')->first();
        $invitedBy = User::factory()->create();
        $token = 'test-token-123';

        $mail = new OrganizationInvitationMail(
            $organization,
            $role,
            $invitedBy,
            $token,
            'John Doe'
        );

        $this->assertEquals(
            "You've been invited to join Test Company on Addy",
            $mail->envelope()->subject
        );
    }

    /** @test */
    public function it_generates_correct_accept_url()
    {
        $organization = Organization::factory()->create();
        $role = OrganizationRole::where('slug', 'admin')->first();
        $invitedBy = User::factory()->create();
        $token = 'my-unique-token';

        $mail = new OrganizationInvitationMail(
            $organization,
            $role,
            $invitedBy,
            $token,
            null
        );

        $this->assertStringContainsString('/invitations/accept/my-unique-token', $mail->acceptUrl);
    }

    /** @test */
    public function it_includes_all_required_data()
    {
        $organization = Organization::factory()->create(['name' => 'My Org']);
        $role = OrganizationRole::where('slug', 'accountant')->first();
        $invitedBy = User::factory()->create(['name' => 'Jane Smith']);
        $token = 'abc123';

        $mail = new OrganizationInvitationMail(
            $organization,
            $role,
            $invitedBy,
            $token,
            'Bob Johnson'
        );

        $this->assertEquals('My Org', $mail->organization->name);
        $this->assertEquals('accountant', $mail->role->slug);
        $this->assertEquals('Jane Smith', $mail->invitedBy->name);
        $this->assertEquals('Bob Johnson', $mail->inviteeName);
        $this->assertEquals('abc123', $mail->inviteToken);
    }

    /** @test */
    public function it_handles_null_invitee_name()
    {
        $organization = Organization::factory()->create();
        $role = OrganizationRole::where('slug', 'member')->first();
        $invitedBy = User::factory()->create();

        $mail = new OrganizationInvitationMail(
            $organization,
            $role,
            $invitedBy,
            'token',
            null
        );

        $this->assertNull($mail->inviteeName);
    }

    /** @test */
    public function it_renders_correctly()
    {
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $role = OrganizationRole::where('slug', 'admin')->first();
        $invitedBy = User::factory()->create(['name' => 'John Doe']);

        $mail = new OrganizationInvitationMail(
            $organization,
            $role,
            $invitedBy,
            'render-test-token',
            'Jane Smith'
        );

        $rendered = $mail->render();

        $this->assertStringContainsString('Acme Corp', $rendered);
        $this->assertStringContainsString('Admin', $rendered);
        $this->assertStringContainsString('John Doe', $rendered);
        $this->assertStringContainsString('Jane Smith', $rendered);
        $this->assertStringContainsString('Accept Invitation', $rendered);
        $this->assertStringContainsString('render-test-token', $rendered);
    }
}
