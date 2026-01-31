<?php

namespace Tests\Unit\Customers;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_reads_updates_and_soft_deletes_a_customer(): void
    {
        // Create
        $customer = Customer::factory()->create([
            'name' => 'Acme Corp',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Acme Corp',
        ]);

        // Read/Update
        $customer->update(['name' => 'Acme Corporation']);
        $this->assertEquals('Acme Corporation', $customer->fresh()->name);

        // Delete (soft)
        $customer->delete();
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
