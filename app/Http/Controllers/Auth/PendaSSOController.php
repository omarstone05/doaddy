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
use Inertia\Inertia;

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
            $pendaOrganizations = $userData['organizations'] ?? [];
            $entitledApps = $userData['entitlements']['apps'] ?? [];

            // Find or create local user
            $user = $this->findOrCreateUser($pendaUser, $tokens, $pendaOrganizations);

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

            // Enforce Addy subscription access via Penda Cloud entitlements
            if (!in_array('addy', $entitledApps, true)) {
                return redirect('/login')->withErrors([
                    'sso' => 'You do not have an active Addy subscription. Please contact your admin or update your plan in Penda Cloud.',
                ]);
            }

            // Sync organizations from Penda Cloud
            $this->syncOrganizations($user, $pendaOrganizations, $accessToken);

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

            // Store entitlements in session for UI
            $request->session()->put('penda_entitled_apps', $entitledApps);

            // Determine organization context
            $organizations = $user->organizations()->wherePivot('is_active', true)->get();
            $currentOrgId = $pendaUser['current_organization']['id'] ?? null
                ?? session('current_organization_id')
                ?? ($organizations->count() === 1 ? $organizations->first()?->id : null);

            if ($currentOrgId) {
                session(['current_organization_id' => $currentOrgId]);
                $user->update(['organization_id' => $currentOrgId]);
            }

            // Persist intended redirect for use after org picker
            $defaultRedirect = '/dashboard';
            $request->session()->put(
                'post_login_redirect',
                $request->session()->get('url.intended', $defaultRedirect)
            );

            Log::info('Penda SSO: User logged in successfully', [
                'user_id' => $user->id,
                'penda_account_id' => $user->penda_account_id,
            ]);

            // If user has multiple orgs, let them choose before entering the app
            if ($organizations->count() > 1 && !$request->session()->get('org_picker_completed')) {
                return redirect()->route('auth.choose-organization');
            }

            $redirectTo = $request->session()->pull('post_login_redirect', $defaultRedirect);

            return redirect()->intended($redirectTo ?? '/dashboard');

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
     * Sync user's organizations from Penda Cloud.
     */
    protected function syncOrganizations(User $user, array $pendaOrganizations, string $accessToken): void
    {
        if (empty($pendaOrganizations)) {
            return;
        }

        foreach ($pendaOrganizations as $pendaOrg) {
            $orgId = $pendaOrg['id'] ?? null;
            if (!$orgId) {
                continue;
            }

            // Find or create organization
            $organization = \App\Models\Organization::firstOrCreate(
                ['id' => $orgId],
                [
                    'name' => $pendaOrg['name'] ?? 'Organization',
                    'slug' => $pendaOrg['slug'] ?? \Illuminate\Support\Str::slug($pendaOrg['name'] ?? 'organization'),
                    'currency' => $pendaOrg['currency'] ?? 'USD',
                    'timezone' => $pendaOrg['timezone'] ?? 'UTC',
                    'status' => $pendaOrg['status'] ?? 'active',
                    'uuid' => $pendaOrg['uuid'] ?? null,
                    'logo' => $pendaOrg['logo'] ?? null,
                ]
            );

            // Always refresh organization details from Penda Cloud (source of truth)
            $organization->fill([
                'name' => $pendaOrg['name'] ?? $organization->name,
                'slug' => $pendaOrg['slug'] ?? $organization->slug,
                'currency' => $pendaOrg['currency'] ?? $organization->currency,
                'timezone' => $pendaOrg['timezone'] ?? $organization->timezone,
                'status' => $pendaOrg['status'] ?? $organization->status,
                'uuid' => $pendaOrg['uuid'] ?? $organization->uuid,
                'logo' => $pendaOrg['logo'] ?? $organization->logo,
            ]);
            $organization->save();

            // Attach user to organization if not already attached
            if (!$user->belongsToOrganization($orgId)) {
                $role = $pendaOrg['role'] ?? $pendaOrg['pivot']['role'] ?? 'member';
                
                $user->organizations()->attach($orgId, [
                    'role' => $role,
                    'is_active' => true,
                    'joined_at' => now(),
                ]);

                Log::info('Penda SSO: Attached user to organization', [
                    'user_id' => $user->id,
                    'organization_id' => $orgId,
                    'role' => $role,
                ]);
            } else {
                // Update role if changed
                $currentRole = $user->getRoleInOrganization($orgId);
                $newRole = $pendaOrg['role'] ?? $pendaOrg['pivot']['role'] ?? 'member';
                
                if ($currentRole !== $newRole) {
                    $user->organizations()->updateExistingPivot($orgId, [
                        'role' => $newRole,
                    ]);
                }
            }
        }
    }

    /**
     * Find or create a local user from Penda SSO data.
     */
    protected function findOrCreateUser(array $pendaUser, array $tokens, array $pendaOrganizations = []): ?User
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
            'is_super_admin' => (bool) ($pendaUser['is_super_admin'] ?? false),
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

    /**
     * Show organization picker after SSO when user has multiple organizations.
     */
    public function chooseOrganization(Request $request)
    {
        $user = Auth::user();

        $organizations = $user->organizations()
            ->wherePivot('is_active', true)
            ->get()
            ->map(function ($org) use ($user) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'logo' => $org->logo,
                    'role' => $user->getRoleInOrganization($org->id),
                ];
            });

        $request->session()->put('org_picker_completed', false);

        return Inertia::render('Auth/ChooseOrganization', [
            'organizations' => $organizations,
            'pendaOnboardingUrl' => config('services.penda_sso.base_url', 'https://penda.cloud') . '/onboarding/step-1',
            'next' => $request->session()->get('post_login_redirect', '/dashboard'),
        ]);
    }

    /**
     * Persist selected organization and continue to the app.
     */
    public function storeOrganizationChoice(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|uuid',
        ]);

        $user = Auth::user();
        $organizationId = $request->input('organization_id');

        if (!$user->belongsToOrganization($organizationId)) {
            abort(403, 'You do not belong to this organization.');
        }

        session([
            'current_organization_id' => $organizationId,
            'org_picker_completed' => true,
        ]);

        $user->update(['organization_id' => $organizationId]);

        $redirectTo = $request->session()->pull('post_login_redirect');
        $fallback = '/dashboard';

        return redirect()->intended($redirectTo ?? $fallback)
            ->with('success', 'Organization selected.');
    }

    /**
     * Check if the current SSO token has access to a specific app.
     */
    protected function hasAppAccess(string $accessToken, string $appSlug): bool
    {
        try {
            $response = Http::withToken($accessToken)
                ->post(config('services.penda_sso.base_url') . '/api/sso/check-access', [
                    'app' => $appSlug,
                ]);

            if ($response->successful()) {
                return (bool) ($response->json()['has_access'] ?? false);
            }
        } catch (\Exception $e) {
            Log::warning('Penda SSO: check-access failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
