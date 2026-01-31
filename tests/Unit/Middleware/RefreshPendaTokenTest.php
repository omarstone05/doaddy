<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RefreshPendaToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RefreshPendaTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'services.penda_sso.base_url' => 'https://penda.cloud',
            'services.penda_sso.client_id' => 'test-client-id',
            'services.penda_sso.client_secret' => 'test-client-secret',
        ]);
    }

    /**
     * Test middleware class exists
     */
    public function test_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(RefreshPendaToken::class));
    }

    /**
     * Test middleware is registered in app
     */
    public function test_middleware_is_registered(): void
    {
        // Check that the middleware is registered in the web middleware group
        $middleware = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups();
        
        $webMiddleware = $middleware['web'] ?? [];
        $this->assertContains(
            \App\Http\Middleware\RefreshPendaToken::class,
            $webMiddleware
        );
    }

    /**
     * Test unauthenticated request is handled
     */
    public function test_dashboard_request_without_auth_is_handled(): void
    {
        $response = $this->get('/dashboard');
        
        // Should redirect to login or return error when not authenticated
        $this->assertTrue(
            $response->isRedirect() || 
            in_array($response->status(), [401, 403, 500])
        );
    }

    /**
     * Test middleware passes for authenticated requests
     */
    public function test_authenticated_request_passes_through(): void
    {
        $response = $this->authenticate()->get('/dashboard');
        
        // Should return 200 OK
        $response->assertStatus(200);
    }

    /**
     * Test token refresh is attempted when token expiring
     */
    public function test_token_refresh_is_attempted_when_expiring(): void
    {
        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        // Set token to expire soon
        $this->withSession([
            'penda_token_expires_at' => time() + 60, // 1 minute
            'penda_refresh_token' => 'old-refresh-token',
            'penda_access_token' => 'old-access-token',
        ]);

        $response = $this->authenticate()->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Verify HTTP request was made for token refresh
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api/sso/token');
        });
    }

    /**
     * Test middleware handles refresh failure gracefully
     */
    public function test_middleware_handles_refresh_failure(): void
    {
        Http::fake([
            '*api/sso/token*' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $this->withSession([
            'penda_token_expires_at' => time() + 60,
            'penda_refresh_token' => 'invalid-token',
        ]);

        // Should not throw exception, request should still complete
        $response = $this->authenticate()->get('/dashboard');
        
        $response->assertStatus(200);
    }

    /**
     * Test no refresh when token not expiring soon
     */
    public function test_no_refresh_when_token_valid(): void
    {
        Http::fake();

        // Token expires in 1 hour - no refresh needed
        $this->withSession([
            'penda_token_expires_at' => time() + 3600,
            'penda_refresh_token' => 'valid-token',
        ]);

        $response = $this->authenticate()->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Should not have made any HTTP calls for refresh
        Http::assertNothingSent();
    }

    /**
     * Test no refresh when no expiration set
     */
    public function test_no_refresh_when_no_expiration(): void
    {
        Http::fake();

        // No token expiration in session
        $response = $this->authenticate()->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Should not have made any HTTP calls for refresh
        Http::assertNothingSent();
    }

    /**
     * Test session tokens are updated after successful refresh
     */
    public function test_session_updated_after_successful_refresh(): void
    {
        Http::fake([
            '*api/sso/token*' => Http::response([
                'access_token' => 'brand-new-token',
                'refresh_token' => 'brand-new-refresh',
                'expires_in' => 7200,
            ], 200),
        ]);

        $this->withSession([
            'penda_token_expires_at' => time() + 30, // 30 seconds
            'penda_refresh_token' => 'old-refresh',
            'penda_access_token' => 'old-access',
        ]);

        $response = $this->authenticate()->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Session should be updated with new tokens
        $this->assertEquals('brand-new-token', session('penda_access_token'));
        $this->assertEquals('brand-new-refresh', session('penda_refresh_token'));
    }
}
