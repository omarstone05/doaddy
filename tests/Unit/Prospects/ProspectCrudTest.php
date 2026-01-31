<?php

namespace Tests\Unit\Prospects;

use App\Models\Customer;
use App\Models\Prospect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_updates_stage_and_converts_to_customer(): void
    {
        $prospect = Prospect::factory()->create([
            'name' => 'Jane Doe',
            'company_name' => 'Future Co',
            'stage' => 'lead',
        ]);

        // Stage update
        $prospect->moveToStage('qualified');
        $this->assertEquals('qualified', $prospect->fresh()->stage);
        $this->assertNotNull($prospect->fresh()->stage_changed_at);

        // Conversion
        $customer = $prospect->convertToCustomer([
            'name' => 'Future Co',
            'currency' => 'ZMW',
            'payment_terms' => 'net_30',
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals($customer->id, $prospect->fresh()->converted_to_customer_id);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Future Co']);
    }
}
