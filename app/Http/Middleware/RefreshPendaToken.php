<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RefreshPendaToken
{
    /**
     * Handle an incoming request.
     * 
     * Check if the Penda SSO access token is about to expire and refresh it.
     * Token is refreshed if it expires within the next 5 minutes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        // Check if we have token expiration info
        $expiresAt = session('penda_token_expires_at');
        $refreshToken = session('penda_refresh_token');

        // If no expiration time set, skip (token might be stored differently)
        if (!$expiresAt || !$refreshToken) {
            return $next($request);
        }

        // Check if token expires within the next 5 minutes (300 seconds)
        $expiresInSeconds = $expiresAt - time();
        
        if ($expiresInSeconds <= 300) {
            Log::info('RefreshPendaToken: Token expiring soon, attempting refresh', [
                'expires_in_seconds' => $expiresInSeconds,
                'user_id' => auth()->id(),
            ]);
            
            $this->refreshToken($refreshToken);
        }

        return $next($request);
    }

    /**
     * Refresh the access token using the refresh token.
     */
    protected function refreshToken(string $refreshToken): bool
    {
        try {
            $response = Http::asForm()->timeout(10)->post(
                config('services.penda_sso.base_url') . '/api/sso/token',
                [
                    'grant_type' => 'refresh_token',
                    'client_id' => config('services.penda_sso.client_id'),
                    'client_secret' => config('services.penda_sso.client_secret'),
                    'refresh_token' => $refreshToken,
                ]
            );

            if ($response->successful()) {
                $tokens = $response->json();
                
                // Update session with new tokens
                if (isset($tokens['access_token'])) {
                    session(['penda_access_token' => $tokens['access_token']]);
                }
                if (isset($tokens['refresh_token'])) {
                    session(['penda_refresh_token' => $tokens['refresh_token']]);
                }
                if (isset($tokens['expires_in'])) {
                    session(['penda_token_expires_at' => now()->addSeconds($tokens['expires_in'])->timestamp]);
                }

                Log::info('RefreshPendaToken: Token refreshed successfully', [
                    'user_id' => auth()->id(),
                    'new_expires_in' => $tokens['expires_in'] ?? null,
                ]);

                return true;
            }

            Log::warning('RefreshPendaToken: Token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'user_id' => auth()->id(),
            ]);

            // If refresh fails with 401, the refresh token is invalid
            // Log the user out to force re-authentication
            if ($response->status() === 401) {
                $this->handleExpiredSession();
            }

            return false;

        } catch (\Exception $e) {
            Log::error('RefreshPendaToken: Exception during token refresh', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return false;
        }
    }

    /**
     * Handle expired session - force user to re-authenticate.
     */
    protected function handleExpiredSession(): void
    {
        // Clear session tokens
        session()->forget([
            'penda_access_token',
            'penda_refresh_token',
            'penda_token_expires_at',
        ]);

        Log::info('RefreshPendaToken: Cleared expired session tokens', [
            'user_id' => auth()->id(),
        ]);

        // Note: We don't log out here to avoid disrupting the current request.
        // The next time an API call to Penda fails, it will trigger re-auth.
    }
}
