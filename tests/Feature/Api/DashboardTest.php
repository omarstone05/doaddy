<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Organization;
use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_dashboard_stats()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, ['role' => 'owner']);

        $token = $user->createToken('test-token')->plainTextToken;

        // Create some data
        MoneyAccount::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $organization->id,
            'flow_type' => 'income',
            'status' => 'approved',
            'amount' => 1000,
            'transaction_date' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'stats' => [
                    'total_accounts',
                    'total_revenue',
                    'total_expenses',
                    'net_balance',
                    'revenue_trend',
                    'expense_trend',
                ],
            ]);
    }
}
