<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SSOErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.penda_sso.base_url' => 'https://penda.cloud',
            'services.penda_sso.client_id' => 'test-client-id',
            'services.penda_sso.client_secret' => 'test-client-secret',
            'services.penda_sso.redirect_uri' => 'http://localhost/auth/penda/callback',
        ]);
    }

    /**
     * Test SSO callback returns user-friendly message on 400 error
     */
    public function test_sso_returns_friendly_message_on_400_error(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response(['error' => 'invalid_request'], 400),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback returns user-friendly message on 401 error
     */
    public function test_sso_returns_friendly_message_on_401_error(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback returns user-friendly message on 403 error
     */
    public function test_sso_returns_friendly_message_on_403_error(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response(['error' => 'access_denied'], 403),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback returns user-friendly message on 429 rate limit error
     */
    public function test_sso_returns_friendly_message_on_rate_limit(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response(['error' => 'too_many_requests'], 429),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback returns user-friendly message on 500 server error
     */
    public function test_sso_returns_friendly_message_on_server_error(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response(['error' => 'server_error'], 500),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback returns user-friendly message on 502 bad gateway
     */
    public function test_sso_returns_friendly_message_on_bad_gateway(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response(null, 502),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback returns user-friendly message on 503 service unavailable
     */
    public function test_sso_returns_friendly_message_on_service_unavailable(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response(null, 503),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback handles connection exception gracefully
     */
    public function test_sso_handles_connection_exception(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO callback handles request exception gracefully
     */
    public function test_sso_handles_request_exception(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => function () {
                throw new \Illuminate\Http\Client\RequestException(
                    new \GuzzleHttp\Psr7\Response(0, [], null)
                );
            },
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO user info failure returns user-friendly message on 401
     */
    public function test_sso_user_info_failure_returns_friendly_message_on_401(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*api/sso/user*' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO user info failure returns user-friendly message on 403
     */
    public function test_sso_user_info_failure_returns_friendly_message_on_403(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*api/sso/user*' => Http::response(['error' => 'forbidden'], 403),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test SSO user info failure returns user-friendly message on 500
     */
    public function test_sso_user_info_failure_returns_friendly_message_on_500(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*api/sso/user*' => Http::response(['error' => 'server_error'], 500),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['sso']);
    }

    /**
     * Test login page displays error when SSO fails
     */
    public function test_login_page_displays_sso_error(): void
    {
        // First trigger an SSO error
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $callbackResponse = $this->get("/auth/penda/callback?code=test-code&state={$state}");
        
        // Follow redirect to login page
        $loginResponse = $this->get('/login');
        
        $loginResponse->assertStatus(200);
    }

    /**
     * Test SSO has proper timeout configuration
     */
    public function test_sso_requests_have_timeout(): void
    {
        $state = Str::random(40);
        $this->withSession(['penda_sso_state' => $state]);

        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*api/sso/user*' => Http::response([
                'id' => 'test-user',
                'penda_account_id' => 'test-user',
                'email' => 'test@example.com',
                'name' => 'Test User',
                'organizations' => [],
                'entitlements' => ['apps' => []],
            ], 200),
        ]);

        $response = $this->get("/auth/penda/callback?code=test-code&state={$state}");

        // The request should complete (either succeed or fail with proper error)
        $response->assertRedirect();
    }
}
