<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\UserMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PendaSSOController extends Controller
{
    /**
     * Redirect to Penda Cloud for SSO authentication.
     */
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('penda_sso_state', $state);

        $query = http_build_query([
            'client_id' => config('services.penda_sso.client_id'),
            'redirect_uri' => config('services.penda_sso.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'user:read organization:read',
            'state' => $state,
        ]);

        $pendaUrl = config('services.penda_sso.base_url', 'https://penda.cloud');

        return redirect("{$pendaUrl}/oauth/authorize?{$query}");
    }

    /**
     * Handle callback from Penda Cloud SSO.
     */
    public function callback(Request $request)
    {
        // Verify state to prevent CSRF
        $storedState = $request->session()->pull('penda_sso_state');
        
        if (!$storedState || $storedState !== $request->input('state')) {
            Log::warning('Penda SSO: Invalid state parameter');
            return redirect('/login')->withErrors([
                'sso' => 'Invalid authentication state. Please try again.',
            ]);
        }

        // Check for error response
        if ($request->has('error')) {
            Log::warning('Penda SSO: Error from provider', [
                'error' => $request->input('error'),
                'description' => $request->input('error_description'),
            ]);
            return redirect('/login')->withErrors([
                'sso' => $request->input('error_description', 'Authentication failed.'),
            ]);
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect('/login')->withErrors([
                'sso' => 'No authorization code received.',
            ]);
        }

        try {
            // Exchange code for access token
            $tokenResponse = Http::asForm()->post(
                config('services.penda_sso.base_url') . '/api/sso/token',
                [
                    'grant_type' => 'authorization_code',
                    'client_id' => config('services.penda_sso.client_id'),
                    'client_secret' => config('services.penda_sso.client_secret'),
                    'redirect_uri' => config('services.penda_sso.redirect_uri'),
                    'code' => $code,
                ]
            );

            if (!$tokenResponse->successful()) {
                Log::error('Penda SSO: Token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body(),
                ]);
                return redirect('/login')->withErrors([
                    'sso' => 'Failed to authenticate with Penda Cloud.',
                ]);
            }

            $tokens = $tokenResponse->json();
            $accessToken = $tokens['access_token'] ?? null;

            if (!$accessToken) {
                return redirect('/login')->withErrors([
                    'sso' => 'No access token received.',
                ]);
            }

            // Get user info from Penda Cloud (includes organizations and subscriptions)
            $userResponse = Http::withToken($accessToken)->get(
                config('services.penda_sso.base_url') . '/api/sso/user'
            );

            if (!$userResponse->successful()) {
                Log::error('Penda SSO: Failed to get user info', [
                    'status' => $userResponse->status(),
                    'body' => $userResponse->body(),
                ]);
                return redirect('/login')->withErrors([
                    'sso' => 'Failed to retrieve user information.',
                ]);
            }

            $userData = $userResponse->json();
            $pendaUser = $userData;

            // Find or create local user
            $user = $this->findOrCreateUser($pendaUser, $tokens);

            if (!$user) {
                return redirect('/login')->withErrors([
                    'sso' => 'Failed to create or update user account.',
                ]);
            }

            // Check if account is active
            if (!$user->is_active) {
                SecurityEvent::logLoginFailure(
                    $user->email,
                    'Account deactivated (SSO)',
                    $request
                );
                return redirect('/login')->withErrors([
                    'sso' => 'Your account has been deactivated. Please contact support.',
                ]);
            }

            // Log the user in
            Auth::login($user, true);
            $request->session()->regenerate();

            // Log successful SSO login
            SecurityEvent::logLoginSuccess($user, $request, [
                'login_method' => 'penda_sso',
                'penda_account_id' => $pendaUser['penda_account_id'] ?? null,
            ]);

            // Track login metric
            try {
                app(UserMetricsService::class)->trackLogin($user);
            } catch (\Exception $e) {
                Log::warning('Failed to track SSO login metric', ['error' => $e->getMessage()]);
            }

            // Set current organization
            $currentOrgId = session('current_organization_id')
                ?? $user->organizations()->first()?->id;

            if ($currentOrgId) {
                session(['current_organization_id' => $currentOrgId]);
            }

            Log::info('Penda SSO: User logged in successfully', [
                'user_id' => $user->id,
                'penda_account_id' => $user->penda_account_id,
            ]);

            // Redirect based on role
            if ($user->isSuperAdmin()) {
                return redirect()->intended('/admin/dashboard');
            }

            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            Log::error('Penda SSO: Exception during callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect('/login')->withErrors([
                'sso' => 'An error occurred during authentication. Please try again.',
            ]);
        }
    }

    /**
     * Find or create a local user from Penda SSO data.
     */
    protected function findOrCreateUser(array $pendaUser, array $tokens): ?User
    {
        $pendaAccountId = $pendaUser['penda_account_id'] ?? $pendaUser['id'] ?? null;
        $email = $pendaUser['email'] ?? null;

        if (!$pendaAccountId || !$email) {
            Log::error('Penda SSO: Missing required user data', ['data' => $pendaUser]);
            return null;
        }

        // Try to find by penda_account_id first, then by email
        $user = User::where('penda_account_id', $pendaAccountId)
            ->orWhere('email', $email)
            ->first();

        $userData = [
            'penda_account_id' => $pendaAccountId,
            'name' => $pendaUser['name'] ?? $email,
            'email' => $email,
            'avatar' => $pendaUser['avatar'] ?? null,
            'last_login_ip' => request()->ip(),
            'last_active_at' => now(),
        ];

        // Store tokens in session instead of user model
        if (isset($tokens['access_token'])) {
            session(['penda_access_token' => $tokens['access_token']]);
        }
        if (isset($tokens['refresh_token'])) {
            session(['penda_refresh_token' => $tokens['refresh_token']]);
        }
        if (isset($tokens['expires_in'])) {
            session(['penda_token_expires_at' => now()->addSeconds($tokens['expires_in'])->timestamp]);
        }

        if ($user) {
            // Update existing user
            $user->update($userData);
            Log::info('Penda SSO: Updated existing user', ['user_id' => $user->id]);
        } else {
            // Create new user
            $userData['password'] = bcrypt(Str::random(32)); // Random password (not used for SSO)
            $userData['email_verified_at'] = now(); // SSO users are verified
            $userData['is_active'] = true;
            
            $user = User::create($userData);
            Log::info('Penda SSO: Created new user', ['user_id' => $user->id]);
        }

        return $user;
    }

    /**
     * Logout and redirect to Penda Cloud logout.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            SecurityEvent::logLogout($user, $request);
        }

        // Revoke token in Penda Cloud if we have one
        $token = session('penda_access_token');
        if ($token) {
            try {
                Http::withToken($token)
                    ->post(config('services.penda_sso.base_url') . '/api/sso/logout');
            } catch (\Exception $e) {
                Log::warning('Penda SSO: Failed to revoke token on logout', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Always redirect to Penda Cloud logout
        $pendaUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
        return redirect("{$pendaUrl}/logout?redirect=" . urlencode(url('/login')));
    }
}






