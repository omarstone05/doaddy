<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Find or create user
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            $isNewUser = false;

            if ($user) {
                // Update existing user with Google ID if not set
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                    ]);
                }
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(), // Google emails are verified
                    'password' => bcrypt(str()->random(32)), // Random password
                ]);
                $isNewUser = true;
            }

            // Log the user in
            Auth::login($user, true);
            $request->session()->regenerate();

            // Set current organization in session
            $currentOrgId = session('current_organization_id') 
                ?? ($user->attributes['organization_id'] ?? null)
                ?? $user->organizations()->first()?->id;
            
            if ($currentOrgId) {
                session(['current_organization_id' => $currentOrgId]);
                $user->update(['organization_id' => $currentOrgId]);
            }

            // If new user and no organization, redirect to onboarding
            if ($isNewUser && !$currentOrgId) {
                // Send welcome email for new Google users
                try {
                    // Create a temporary organization for welcome email (will be created in onboarding)
                    // Or send welcome without organization
                    $emailService = app(\App\Services\Admin\EmailService::class);
                    // We'll send welcome email after organization is created in onboarding
                } catch (\Exception $e) {
                    \Log::warning('Failed to send welcome email for Google user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                return redirect()->route('onboarding');
            }

            // Redirect super admins to admin dashboard
            if ($user->isSuperAdmin()) {
                return redirect()->intended('/admin/dashboard');
            }

            // If user has no organization, redirect to onboarding
            if (!$currentOrgId) {
                return redirect()->route('onboarding');
            }

            return redirect()->intended('/dashboard');
        } catch (\Exception $e) {
            Log::error('Google login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/login')->with('error', 'Failed to login with Google. Please try again.');
        }
    }
}

