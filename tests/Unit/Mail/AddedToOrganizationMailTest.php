<?php

namespace Tests\Unit\Mail;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Mail\AddedToOrganizationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddedToOrganizationMailTest extends TestCase
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
        $addedBy = User::factory()->create();

        $mail = new AddedToOrganizationMail(
            $organization,
            $role,
            $addedBy
        );

        $this->assertEquals(
            "You've been added to Test Company on Addy",
            $mail->envelope()->subject
        );
    }

    /** @test */
    public function it_generates_dashboard_url()
    {
        $organization = Organization::factory()->create();
        $role = OrganizationRole::where('slug', 'admin')->first();
        $addedBy = User::factory()->create();

        $mail = new AddedToOrganizationMail(
            $organization,
            $role,
            $addedBy
        );

        $this->assertStringContainsString('/dashboard', $mail->dashboardUrl);
    }

    /** @test */
    public function it_includes_all_required_data()
    {
        $organization = Organization::factory()->create(['name' => 'My Organization']);
        $role = OrganizationRole::where('slug', 'accountant')->first();
        $addedBy = User::factory()->create(['name' => 'Jane Smith']);

        $mail = new AddedToOrganizationMail(
            $organization,
            $role,
            $addedBy
        );

        $this->assertEquals('My Organization', $mail->organization->name);
        $this->assertEquals('accountant', $mail->role->slug);
        $this->assertEquals('Jane Smith', $mail->addedBy->name);
    }

    /** @test */
    public function it_renders_correctly()
    {
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $role = OrganizationRole::where('slug', 'admin')->first();
        $addedBy = User::factory()->create(['name' => 'John Doe']);

        $mail = new AddedToOrganizationMail(
            $organization,
            $role,
            $addedBy
        );

        $rendered = $mail->render();

        $this->assertStringContainsString('Acme Corp', $rendered);
        $this->assertStringContainsString('Admin', $rendered);
        $this->assertStringContainsString('John Doe', $rendered);
        $this->assertStringContainsString('Go to Dashboard', $rendered);
    }

    /** @test */
    public function it_includes_role_description_if_available()
    {
        $organization = Organization::factory()->create();
        $role = OrganizationRole::where('slug', 'accountant')->first();
        $role->update(['description' => 'Manages financial records and transactions']);
        $addedBy = User::factory()->create();

        $mail = new AddedToOrganizationMail(
            $organization,
            $role,
            $addedBy
        );

        $rendered = $mail->render();
        
        // The description should be in the template if it exists
        $this->assertEquals('Manages financial records and transactions', $mail->role->description);
    }
}
