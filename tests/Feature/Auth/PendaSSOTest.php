<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PendaSSOTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up Penda SSO config
        config([
            'services.penda_sso.base_url' => 'https://penda.cloud',
            'services.penda_sso.client_id' => 'test-client-id',
            'services.penda_sso.client_secret' => 'test-client-secret',
            'services.penda_sso.redirect_uri' => 'http://localhost/auth/penda/callback',
        ]);
    }

    /**
     * Test SSO redirect initiates OAuth flow
     */
    public function test_sso_redirect_initiates_oauth_flow(): void
    {
        $response = $this->get('/auth/penda');

        $response->assertStatus(302);
        $response->assertRedirectContains('penda.cloud/oauth/authorize');
        $response->assertRedirectContains('client_id=test-client-id');
        $response->assertRedirectContains('response_type=code');
    }

    /**
     * Test SSO redirect stores state in session
     */
    public function test_sso_redirect_stores_state_in_session(): void
    {
        $response = $this->get('/auth/penda');

        $response->assertSessionHas('penda_sso_state');
    }

    /**
     * Test SSO redirect stores intended URL
     */
    public function test_sso_redirect_stores_intended_url(): void
    {
        $response = $this->get('/auth/penda?redirect=/dashboard/reports');

        $response->assertSessionHas('sso_intended_url', '/dashboard/reports');
    }

    /**
     * Test SSO callback fails without state
     */
    public function test_sso_callback_fails_without_state(): void
    {
        $response = $this->get('/auth/penda/callback?code=test-code');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback fails with invalid state
     */
    public function test_sso_callback_fails_with_invalid_state(): void
    {
        $this->withSession(['penda_sso_state' => 'valid-state']);

        $response = $this->get('/auth/penda/callback?code=test-code&state=invalid-state');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback handles error response from provider
     */
    public function test_sso_callback_handles_error_response(): void
    {
        $this->withSession(['penda_sso_state' => 'valid-state']);

        $response = $this->get('/auth/penda/callback?error=access_denied&error_description=User+denied+access&state=valid-state');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback fails without authorization code
     */
    public function test_sso_callback_fails_without_code(): void
    {
        $this->withSession(['penda_sso_state' => 'valid-state']);

        $response = $this->get('/auth/penda/callback?state=valid-state');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test successful SSO callback creates new user
     */
    public function test_sso_callback_creates_new_user(): void
    {
        $state = Str::random(40);
        $this->withSession([
            'penda_sso_state' => $state,
            'sso_intended_url' => '/dashboard',
        ]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'expires_in' => 3600,
            ], 200),
            '*api/sso/user*' => Http::response([
                'id' => 'penda-user-123',
                'penda_account_id' => 'penda-user-123',
                'name' => 'Test User',
                'email' => 'newuser@example.com',
                'avatar' => null,
                'is_super_admin' => false,
                'organizations' => [
                    [
                        'id' => 'org-uuid-123',
                        'name' => 'Test Org',
                        'slug' => 'test-org',
                        'role' => 'owner',
                    ],
                ],
                'entitlements' => [
                    'apps' => ['addy', 'projjo'],
                ],
                'current_organization' => [
                    'id' => 'org-uuid-123',
                ],
            ], 200),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        // User should be created in the database
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'penda_account_id' => 'penda-user-123',
        ]);

        // Should redirect
        $response->assertRedirect();
    }

    /**
     * Test SSO callback updates existing user
     */
    public function test_sso_callback_updates_existing_user(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'penda_account_id' => 'penda-user-456',
            'name' => 'Old Name',
            'is_active' => true,
        ]);

        $state = Str::random(40);
        $this->withSession([
            'penda_sso_state' => $state,
            'sso_intended_url' => '/dashboard',
        ]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*api/sso/user*' => Http::response([
                'id' => 'penda-user-456',
                'penda_account_id' => 'penda-user-456',
                'name' => 'Updated Name',
                'email' => 'existing@example.com',
                'organizations' => [
                    [
                        'id' => 'org-uuid-789',
                        'name' => 'User Org',
                        'slug' => 'user-org',
                        'role' => 'member',
                    ],
                ],
                'entitlements' => [
                    'apps' => ['addy'],
                ],
                'current_organization' => [
                    'id' => 'org-uuid-789',
                ],
            ], 200),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        // User name should be updated in database
        $existingUser->refresh();
        $this->assertEquals('Updated Name', $existingUser->name);

        $response->assertRedirect();
    }

    /**
     * Test SSO callback fails for deactivated user
     */
    public function test_sso_callback_fails_for_deactivated_user(): void
    {
        User::factory()->create([
            'email' => 'deactivated@example.com',
            'penda_account_id' => 'penda-user-789',
            'is_active' => false,
        ]);

        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*api/sso/user*' => Http::response([
                'id' => 'penda-user-789',
                'penda_account_id' => 'penda-user-789',
                'email' => 'deactivated@example.com',
                'name' => 'Deactivated User',
                'organizations' => [],
                'entitlements' => [
                    'apps' => ['addy'],
                ],
            ], 200),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback fails without Addy entitlement
     */
    public function test_sso_callback_fails_without_addy_entitlement(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*api/sso/user*' => Http::response([
                'id' => 'penda-no-access',
                'penda_account_id' => 'penda-no-access',
                'email' => 'noaccess@example.com',
                'name' => 'No Access User',
                'organizations' => [],
                'entitlements' => [
                    'apps' => ['projjo'], // No Addy access
                ],
            ], 200),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback handles token exchange failure
     */
    public function test_sso_callback_handles_token_exchange_failure(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback handles user info fetch failure
     */
    public function test_sso_callback_handles_user_info_failure(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*api/sso/user*' => Http::response([
                'error' => 'Unauthorized',
            ], 401),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO logout redirects to Penda Cloud
     */
    public function test_sso_logout_redirects_to_penda_cloud(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Http::fake([
            '*api/sso/logout*' => Http::response([], 200),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['penda_access_token' => 'test-token'])
            ->post('/logout');

        $response->assertRedirectContains('penda.cloud/logout');
        $this->assertGuest();
    }

    /**
     * Test choose organization page is accessible for multi-org users
     */
    public function test_choose_organization_page_is_accessible(): void
    {
        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $org2 = Organization::factory()->create(['name' => 'Org 2']);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->organizations()->attach($org1->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $user->organizations()->attach($org2->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('auth.choose-organization'));

        $response->assertStatus(200);
    }

    /**
     * Test storing organization choice
     */
    public function test_store_organization_choice(): void
    {
        $org = Organization::factory()->create();

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->organizations()->attach($org->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['post_login_redirect' => '/dashboard/reports'])
            ->post(route('auth.store-organization-choice'), [
                'organization_id' => $org->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('current_organization_id', $org->id);
    }

    /**
     * Test cannot select organization user doesn't belong to
     */
    public function test_cannot_select_organization_user_does_not_belong_to(): void
    {
        $org = Organization::factory()->create();

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        // User is NOT attached to org

        $response = $this->actingAs($user)
            ->post(route('auth.store-organization-choice'), [
                'organization_id' => $org->id,
            ]);

        $response->assertStatus(403);
    }
}
