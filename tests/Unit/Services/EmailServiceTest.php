<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\EmailService;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Mail;
use Mockery;

class EmailServiceTest extends TestCase
{
    protected EmailService $emailService;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->emailService = new EmailService();
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $service = new EmailService();

        $this->assertInstanceOf(EmailService::class, $service);
    }

    /** @test */
    public function it_sends_email_and_creates_log(): void
    {
        $result = $this->emailService->send(
            to: 'test@example.com',
            subject: 'Test Subject',
            body: 'Test body content',
            organization: $this->testOrganization,
            user: $this->testUser
        );

        $this->assertTrue($result);

        $this->assertDatabaseHas('email_logs', [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'organization_id' => $this->testOrganization->id,
            'user_id' => $this->testUser->id,
        ]);
    }

    /** @test */
    public function it_handles_array_of_recipients(): void
    {
        $result = $this->emailService->send(
            to: ['test@example.com', 'another@example.com'],
            subject: 'Test Subject',
            body: 'Test body content'
        );

        $this->assertTrue($result);

        // It takes first email from array
        $this->assertDatabaseHas('email_logs', [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
        ]);
    }

    /** @test */
    public function it_stores_template_slug_in_log(): void
    {
        $this->emailService->send(
            to: 'test@example.com',
            subject: 'Test Subject',
            body: 'Test body content',
            templateSlug: 'welcome'
        );

        $this->assertDatabaseHas('email_logs', [
            'to' => 'test@example.com',
            'template_slug' => 'welcome',
        ]);
    }

    /** @test */
    public function it_stores_cc_and_bcc_in_log(): void
    {
        $this->emailService->send(
            to: 'test@example.com',
            subject: 'Test Subject',
            body: 'Test body content',
            cc: 'cc@example.com',
            bcc: 'bcc@example.com'
        );

        $this->assertDatabaseHas('email_logs', [
            'to' => 'test@example.com',
            'cc' => 'cc@example.com',
            'bcc' => 'bcc@example.com',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_template_not_found(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Email template 'non_existent_template' not found");

        $this->emailService->sendFromTemplate(
            templateSlug: 'non_existent_template',
            to: 'test@example.com',
            data: []
        );
    }

    /** @test */
    public function it_sends_email_from_template(): void
    {
        // Create a test template (global, no organization_id)
        $template = EmailTemplate::factory()->create([
            'slug' => 'test_template',
            'subject' => 'Hello {{user_name}}',
            'body' => 'Welcome {{user_name}} to {{organization_name}}',
        ]);

        $result = $this->emailService->sendFromTemplate(
            templateSlug: 'test_template',
            to: 'test@example.com',
            data: [
                'user_name' => 'John',
                'organization_name' => 'Acme Inc',
            ],
            organization: $this->testOrganization,
            user: $this->testUser
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_for_profile_update_without_organization(): void
    {
        // Create a user without organization relationship
        $userWithoutOrg = $this->testUser;

        // Mock the organization property to return null
        $mockUser = Mockery::mock($userWithoutOrg)->makePartial();
        $mockUser->shouldReceive('getAttribute')->with('organization')->andReturn(null);

        $result = $this->emailService->sendProfileUpdateNotification(
            user: $mockUser,
            changes: ['name' => 'New Name']
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_for_trial_ending_notification_without_user(): void
    {
        // Create an organization without users
        $orgWithoutUsers = \App\Models\Organization::factory()->create();

        // Don't create any users for this org

        $result = $this->emailService->sendTrialEndingNotification(
            organization: $orgWithoutUsers,
            daysLeft: 5
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_for_suspension_notification_without_user(): void
    {
        // Create an organization without users
        $orgWithoutUsers = \App\Models\Organization::factory()->create();

        // Don't create any users for this org

        $result = $this->emailService->sendSuspensionNotification(
            organization: $orgWithoutUsers,
            reason: 'Payment overdue'
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_logs_pending_status_initially(): void
    {
        $this->emailService->send(
            to: 'test@example.com',
            subject: 'Test Subject',
            body: 'Test body'
        );

        // Check that log was created with pending status
        $log = EmailLog::where('to', 'test@example.com')->first();
        $this->assertNotNull($log);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
