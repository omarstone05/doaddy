<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Vendor;
use App\Models\Bill;

class VendorTest extends TestCase
{
    /** @test */
    public function it_generates_vendor_code_automatically(): void
    {
        $vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Test Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $this->assertNotNull($vendor->vendor_code);
        $this->assertStringStartsWith('VEN', $vendor->vendor_code);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Test Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $this->assertEquals($this->testOrganization->id, $vendor->organization->id);
    }

    /** @test */
    public function it_calculates_payment_terms_days_correctly(): void
    {
        $immediateVendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Immediate Vendor',
            'payment_terms' => 'immediate',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $net15Vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Net 15 Vendor',
            'payment_terms' => 'net_15',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $net30Vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Net 30 Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $customVendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Custom Vendor',
            'payment_terms' => 'custom',
            'custom_payment_days' => 45,
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $this->assertEquals(0, $immediateVendor->payment_terms_days);
        $this->assertEquals(15, $net15Vendor->payment_terms_days);
        $this->assertEquals(30, $net30Vendor->payment_terms_days);
        $this->assertEquals(45, $customVendor->payment_terms_days);
    }

    /** @test */
    public function it_scopes_active_vendors(): void
    {
        Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Active Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Inactive Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'inactive',
        ]);

        $activeVendors = Vendor::where('organization_id', $this->testOrganization->id)
            ->active()
            ->get();

        $this->assertCount(1, $activeVendors);
        $this->assertEquals('Active Vendor', $activeVendors->first()->name);
    }

    /** @test */
    public function it_scopes_vendors_with_outstanding_balance(): void
    {
        Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Vendor With Balance',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
            'outstanding_balance' => 5000,
        ]);

        Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Vendor No Balance',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
            'outstanding_balance' => 0,
        ]);

        $vendorsWithBalance = Vendor::where('organization_id', $this->testOrganization->id)
            ->withOutstandingBalance()
            ->get();

        $this->assertCount(1, $vendorsWithBalance);
        $this->assertEquals(5000, $vendorsWithBalance->first()->outstanding_balance);
    }

    /** @test */
    public function it_casts_arrays_correctly(): void
    {
        $vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Test Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
            'tags' => ['preferred', 'local'],
            'custom_fields' => ['category' => 'electronics', 'region' => 'lusaka'],
        ]);

        $this->assertIsArray($vendor->tags);
        $this->assertIsArray($vendor->custom_fields);
        $this->assertContains('preferred', $vendor->tags);
        $this->assertEquals('electronics', $vendor->custom_fields['category']);
    }

    /** @test */
    public function it_stores_bank_information(): void
    {
        $vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Test Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
            'bank_name' => 'First National Bank',
            'bank_account_number' => '1234567890',
            'bank_routing_number' => 'FNBZZMLU',
        ]);

        $this->assertEquals('First National Bank', $vendor->bank_name);
        $this->assertEquals('1234567890', $vendor->bank_account_number);
        $this->assertEquals('FNBZZMLU', $vendor->bank_routing_number);
    }

    /** @test */
    public function it_handles_primary_contact_fields(): void
    {
        $vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Test Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
            'primary_contact_name' => 'Jane Smith',
            'primary_contact_email' => 'jane@vendor.com',
            'primary_contact_phone' => '+260123456789',
        ]);

        $this->assertEquals('Jane Smith', $vendor->primary_contact_name);
        $this->assertEquals('jane@vendor.com', $vendor->primary_contact_email);
        $this->assertEquals('+260123456789', $vendor->primary_contact_phone);
    }

    /** @test */
    public function it_casts_dates_correctly(): void
    {
        $vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Test Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
            'first_transaction_date' => '2024-01-15',
            'last_transaction_date' => '2024-06-20',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $vendor->first_transaction_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $vendor->last_transaction_date);
    }

    /** @test */
    public function it_soft_deletes_vendors(): void
    {
        $vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Test Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $vendorId = $vendor->id;
        $vendor->delete();

        // Vendor should not appear in normal queries
        $this->assertNull(Vendor::find($vendorId));

        // Vendor should appear when including trashed
        $this->assertNotNull(Vendor::withTrashed()->find($vendorId));
    }

    /** @test */
    public function it_respects_organization_isolation(): void
    {
        $otherOrg = $this->createOtherOrganization();

        Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Test Org Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        Vendor::create([
            'organization_id' => $otherOrg->id,
            'type' => 'supplier',
            'name' => 'Other Org Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $testOrgVendors = Vendor::where('organization_id', $this->testOrganization->id)->get();

        $this->assertCount(1, $testOrgVendors);
        $this->assertEquals('Test Org Vendor', $testOrgVendors->first()->name);
    }

    /** @test */
    public function it_stores_rating(): void
    {
        $vendor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Excellent Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
            'rating' => 5,
        ]);

        $this->assertEquals(5, $vendor->rating);
    }

    /** @test */
    public function it_handles_different_vendor_types(): void
    {
        $supplier = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'supplier',
            'name' => 'Supplier Vendor',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $contractor = Vendor::create([
            'organization_id' => $this->testOrganization->id,
            'type' => 'contractor',
            'name' => 'Contractor Vendor',
            'payment_terms' => 'net_15',
            'currency' => 'ZMW',
            'status' => 'active',
        ]);

        $this->assertEquals('supplier', $supplier->type);
        $this->assertEquals('contractor', $contractor->type);
    }
}
