<?php

namespace Tests\Unit\Modules\CRM;

use Tests\TestCase;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\Contact;
use App\Modules\CRM\Models\Opportunity;
use App\Modules\CRM\Models\Activity;
use App\Models\User;
use Illuminate\Support\Str;

class LeadTest extends TestCase
{
    /** @test */
    public function it_creates_lead_with_required_fields(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'lead_status' => 'new',
        ]);

        $this->assertNotNull($lead->id);
        $this->assertEquals('John', $lead->first_name);
        $this->assertEquals('Doe', $lead->last_name);
    }

    /** @test */
    public function it_generates_full_name_attribute(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'lead_status' => 'new',
        ]);

        $this->assertEquals('Jane Smith', $lead->full_name);
    }

    /** @test */
    public function it_handles_only_first_name(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'new',
        ]);

        $this->assertEquals('John', $lead->full_name);
    }

    /** @test */
    public function it_belongs_to_assigned_user(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'lead_status' => 'new',
            'assigned_to' => $this->testUser->id,
            'assigned_date' => now(),
        ]);

        $this->assertInstanceOf(User::class, $lead->assignedTo);
        $this->assertEquals($this->testUser->id, $lead->assignedTo->id);
    }

    /** @test */
    public function it_casts_arrays_correctly(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'new',
            'interested_in' => ['product_a', 'product_b'],
            'tags' => ['hot', 'priority'],
            'custom_fields' => ['industry' => 'technology', 'size' => 'enterprise'],
        ]);

        $this->assertIsArray($lead->interested_in);
        $this->assertIsArray($lead->tags);
        $this->assertIsArray($lead->custom_fields);
        $this->assertContains('product_a', $lead->interested_in);
        $this->assertEquals('technology', $lead->custom_fields['industry']);
    }

    /** @test */
    public function it_casts_dates_correctly(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'new',
            'assigned_date' => '2024-06-15 10:00:00',
            'next_follow_up_date' => '2024-06-20',
            'last_contacted_at' => '2024-06-14 15:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $lead->assigned_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $lead->next_follow_up_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $lead->last_contacted_at);
    }

    /** @test */
    public function it_casts_booleans_correctly(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'new',
            'is_converted' => false,
            'is_lost' => false,
            'do_not_call' => true,
            'do_not_email' => true,
            'do_not_whatsapp' => false,
        ]);

        $this->assertFalse($lead->is_converted);
        $this->assertFalse($lead->is_lost);
        $this->assertTrue($lead->do_not_call);
        $this->assertTrue($lead->do_not_email);
        $this->assertFalse($lead->do_not_whatsapp);
    }

    /** @test */
    public function it_casts_estimated_value_as_decimal(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'qualified',
            'estimated_value' => 50000.50,
        ]);

        $this->assertEquals('50000.50', $lead->estimated_value);
    }

    /** @test */
    public function it_handles_lead_scoring(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'qualified',
            'lead_score' => 85,
            'rating' => 'hot',
        ]);

        $this->assertEquals(85, $lead->lead_score);
        $this->assertEquals('hot', $lead->rating);
    }

    /** @test */
    public function it_handles_lead_source_tracking(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'new',
            'lead_source' => 'website',
            'source_details' => 'Contact form on pricing page',
        ]);

        $this->assertEquals('website', $lead->lead_source);
        $this->assertEquals('Contact form on pricing page', $lead->source_details);
    }

    /** @test */
    public function it_tracks_budget_and_timeline(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'qualified',
            'budget_range' => '10000-50000',
            'timeline' => '3_months',
        ]);

        $this->assertEquals('10000-50000', $lead->budget_range);
        $this->assertEquals('3_months', $lead->timeline);
    }

    /** @test */
    public function it_soft_deletes_leads(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'new',
        ]);

        $leadId = $lead->id;
        $lead->delete();

        // Lead should not appear in normal queries
        $this->assertNull(Lead::find($leadId));

        // Lead should appear when including trashed
        $this->assertNotNull(Lead::withTrashed()->find($leadId));
    }

    /** @test */
    public function it_handles_company_information(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company_name' => 'Acme Corporation',
            'job_title' => 'CEO',
            'lead_status' => 'new',
        ]);

        $this->assertEquals('Acme Corporation', $lead->company_name);
        $this->assertEquals('CEO', $lead->job_title);
    }

    /** @test */
    public function it_handles_contact_information(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'email' => 'john@example.com',
            'phone' => '+260123456789',
            'secondary_phone' => '+260987654321',
            'whatsapp_number' => '+260123456789',
            'lead_status' => 'new',
        ]);

        $this->assertEquals('john@example.com', $lead->email);
        $this->assertEquals('+260123456789', $lead->phone);
        $this->assertEquals('+260987654321', $lead->secondary_phone);
        $this->assertEquals('+260123456789', $lead->whatsapp_number);
    }

    /** @test */
    public function it_handles_address_information(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'address' => '123 Main Street',
            'city' => 'Lusaka',
            'province' => 'Lusaka Province',
            'country' => 'Zambia',
            'lead_status' => 'new',
        ]);

        $this->assertEquals('123 Main Street', $lead->address);
        $this->assertEquals('Lusaka', $lead->city);
        $this->assertEquals('Zambia', $lead->country);
    }

    /** @test */
    public function it_tracks_lost_leads(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'lost',
            'is_lost' => true,
            'lost_reason' => 'Chose competitor',
            'lost_at' => now(),
        ]);

        $this->assertTrue($lead->is_lost);
        $this->assertEquals('Chose competitor', $lead->lost_reason);
        $this->assertNotNull($lead->lost_at);
    }

    /** @test */
    public function it_handles_conversion_tracking(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'converted',
            'is_converted' => true,
            'converted_at' => now(),
            'converted_by' => $this->testUser->id,
        ]);

        $this->assertTrue($lead->is_converted);
        $this->assertNotNull($lead->converted_at);
        $this->assertEquals($this->testUser->id, $lead->converted_by);
    }

    /** @test */
    public function it_tracks_communication_preferences(): void
    {
        $lead = Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'John',
            'lead_status' => 'new',
            'email_bounced' => true,
            'unsubscribed' => true,
        ]);

        $this->assertTrue($lead->email_bounced);
        $this->assertTrue($lead->unsubscribed);
    }

    /** @test */
    public function it_respects_organization_isolation(): void
    {
        $otherOrg = $this->createOtherOrganization();

        Lead::create([
            'organization_id' => $this->testOrganization->id,
            'first_name' => 'Test Org Lead',
            'lead_status' => 'new',
        ]);

        $otherLead = new Lead([
            'organization_id' => $otherOrg->id,
            'first_name' => 'Other Org Lead',
            'lead_status' => 'new',
        ]);
        $otherLead->saveQuietly();

        $testOrgLeads = Lead::withoutGlobalScope('organization')
            ->where('organization_id', $this->testOrganization->id)
            ->get();

        $this->assertCount(1, $testOrgLeads);
        $this->assertEquals('Test Org Lead', $testOrgLeads->first()->first_name);
    }
}
