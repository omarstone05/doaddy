<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
        ]);
    }

    /**
     * Test login page is accessible
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * Test user can login with valid credentials
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test login fails with non-existent email
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test login fails with wrong password
     */
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correctpassword'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /**
     * Test login fails for deactivated user
     */
    public function test_login_fails_for_deactivated_user(): void
    {
        User::factory()->create([
            'email' => 'deactivated@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'deactivated@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test login requires email
     */
    public function test_login_requires_email(): void
    {
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test login requires password
     */
    public function test_login_requires_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'user@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * Test user can logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        // In Addy, logout redirects to Penda Cloud
        $response->assertRedirect();
        $this->assertGuest();
    }

    /**
     * Test login sets organization session
     */
    public function test_successful_login_sets_organization_session(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'organization_id' => $this->organization->id,
        ]);

        $user->organizations()->attach($this->organization->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $this->assertEquals(
            $this->organization->id,
            session('current_organization_id')
        );
    }

    /**
     * Test register redirects to Penda Cloud
     */
    public function test_register_redirects_to_penda_cloud(): void
    {
        $response = $this->get('/register');

        $response->assertRedirectContains('penda.cloud/register');
    }
}
