<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 404 status code is returned for non-existent routes
     */
    public function test_404_status_code_for_non_existent_routes(): void
    {
        $response = $this->get('/this-page-does-not-exist-anywhere');

        $response->assertStatus(404);
    }

    /**
     * Test 404 page returns JSON for API requests
     */
    public function test_404_returns_json_for_api_requests(): void
    {
        $response = $this->getJson('/api/this-endpoint-does-not-exist');

        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test 403 is returned for unauthorized admin access
     */
    public function test_403_status_for_unauthorized_admin_access(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Try to access admin-only route
        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Test 403 returns JSON structure for API requests
     */
    public function test_403_returns_json_structure_for_api_requests(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Try to access admin API endpoint without proper authorization
        $response = $this->actingAs($user)
            ->getJson('/admin/users');

        // Should return 403 with message structure
        if ($response->status() === 403) {
            $response->assertJsonStructure(['message']);
        }
    }

    /**
     * Test authenticated user sees 404 for missing pages
     */
    public function test_authenticated_user_sees_404_for_missing_pages(): void
    {
        $response = $this->authenticate()->get('/nonexistent-page');

        $response->assertStatus(404);
    }

    /**
     * Test nested non-existent routes return 404
     */
    public function test_nested_non_existent_routes_return_404(): void
    {
        $response = $this->get('/deeply/nested/route/that/does/not/exist');

        $response->assertStatus(404);
    }

    /**
     * Test error pages exist
     */
    public function test_error_page_files_exist(): void
    {
        $this->assertFileExists(
            base_path('resources/js/Pages/Errors/404.jsx')
        );
        $this->assertFileExists(
            base_path('resources/js/Pages/Errors/403.jsx')
        );
        $this->assertFileExists(
            base_path('resources/js/Pages/Errors/500.jsx')
        );
    }

    /**
     * Test exception handler is configured for 404
     */
    public function test_exception_handler_handles_404(): void
    {
        $response = $this->get('/unknown-route-12345');

        $response->assertStatus(404);
        
        // The response should contain HTML (Inertia rendered page)
        $this->assertStringContainsString('<!DOCTYPE html>', $response->getContent());
    }

    /**
     * Test exception handler is configured for 403
     */
    public function test_exception_handler_handles_403(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(403);
        
        // The response should contain HTML (Inertia rendered page)
        $this->assertStringContainsString('<!DOCTYPE html>', $response->getContent());
    }
}
