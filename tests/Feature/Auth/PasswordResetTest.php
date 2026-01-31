<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test forgot password page is accessible
     */
    public function test_forgot_password_page_is_accessible(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    /**
     * Test forgot password requires email (JSON request)
     */
    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson('/forgot-password', []);

        // Should return validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test forgot password requires valid email format (JSON request)
     */
    public function test_forgot_password_requires_valid_email(): void
    {
        $response = $this->postJson('/forgot-password', [
            'email' => 'not-an-email',
        ]);

        // Should return validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test forgot password sends reset link for existing user
     */
    public function test_forgot_password_sends_reset_link_for_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'existing@example.com',
        ]);

        $response->assertSessionHas('status');
        
        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Test forgot password doesn't reveal if user exists
     */
    public function test_forgot_password_redirects_for_nonexistent_user(): void
    {
        // Request reset for non-existent email
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should redirect back (not reveal user doesn't exist)
        $response->assertRedirect();
    }

    /**
     * Test reset password page is accessible with token
     */
    public function test_reset_password_page_is_accessible_with_token(): void
    {
        $response = $this->get('/reset-password/test-token?email=test@example.com');

        $response->assertStatus(200);
    }

    /**
     * Test reset password with valid token changes password
     */
    public function test_reset_password_with_valid_token_changes_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('old-password'),
        ]);

        // Create a valid reset token
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect('/login');

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    /**
     * Test reset password with invalid token fails
     */
    public function test_reset_password_with_invalid_token_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid-token@example.com',
        ]);

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => 'invalid-token@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test password reset controller exists
     */
    public function test_password_reset_link_controller_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Http\Controllers\Auth\PasswordResetLinkController::class)
        );
    }

    /**
     * Test new password controller exists
     */
    public function test_new_password_controller_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Http\Controllers\Auth\NewPasswordController::class)
        );
    }

    /**
     * Test password reset routes are defined
     */
    public function test_password_reset_routes_are_defined(): void
    {
        $this->assertTrue(\Route::has('password.request'));
        $this->assertTrue(\Route::has('password.email'));
        $this->assertTrue(\Route::has('password.reset'));
        $this->assertTrue(\Route::has('password.store'));
    }
}
