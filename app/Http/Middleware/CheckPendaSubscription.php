<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPendaSubscription
{
    protected string $pendaUrl;
    protected string $appId = 'addy';
    protected int $cacheTtl = 300; // 5 minutes cache

    public function __construct()
    {
        $this->pendaUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        $organization = $this->getCurrentOrganization($request);
        
        if (!$organization) {
            // No organization context, allow request (might be personal account)
            return $next($request);
        }

        // Check if organization has access to this app
        $subscription = $this->getSubscriptionStatus($organization->id);
        
        if (!$subscription || $subscription['status'] !== 'active') {
            // Check if it's a trial
            if ($subscription && $subscription['status'] === 'trial') {
                // Allow trial users to continue
                return $next($request);
            }

            // No active subscription
            return $this->handleNoSubscription($request, $organization);
        }

        // If a specific module is required, check module access
        if ($module && !$this->hasModuleAccess($organization->id, $module)) {
            return $this->handleNoModuleAccess($request, $module, $subscription);
        }

        // Store subscription info in request for later use
        $request->attributes->set('penda_subscription', $subscription);

        return $next($request);
    }

    /**
     * Get subscription status for organization
     */
    protected function getSubscriptionStatus(string $organizationId): ?array
    {
        $cacheKey = "penda_subscription_{$this->appId}_{$organizationId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($organizationId) {
            try {
                // First check local database for cached subscription
                $localSub = \DB::table('organization_subscriptions')
                    ->where('organization_id', $organizationId)
                    ->where('app_id', $this->appId)
                    ->first();

                if ($localSub) {
                    return [
                        'status' => $localSub->status,
                        'plan' => $localSub->plan,
                        'expires_at' => $localSub->expires_at,
                        'has_elective_modules' => $localSub->has_elective_modules ?? false,
                        'has_custom_module' => $localSub->has_custom_module ?? false,
                    ];
                }

                // Fall back to Penda Cloud API if no local record
                $response = Http::timeout(5)
                    ->withToken($this->getServiceToken())
                    ->get("{$this->pendaUrl}/api/subscriptions/check", [
                        'organization_id' => $organizationId,
                        'app_id' => $this->appId,
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                return null;
            } catch (\Exception $e) {
                Log::warning('Failed to check Penda subscription', [
                    'organization_id' => $organizationId,
                    'error' => $e->getMessage(),
                ]);
                
                // In case of error, allow access (fail open for better UX)
                // You might want to change this to fail closed in production
                return ['status' => 'active', 'plan' => 'unknown'];
            }
        });
    }

    /**
     * Check if organization has access to a specific module
     */
    protected function hasModuleAccess(string $organizationId, string $module): bool
    {
        $cacheKey = "penda_module_{$this->appId}_{$organizationId}_{$module}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($organizationId, $module) {
            try {
                // Check local module access table
                $hasAccess = \DB::table('organization_modules')
                    ->where('organization_id', $organizationId)
                    ->where('module_slug', $module)
                    ->where('is_active', true)
                    ->exists();

                if ($hasAccess) {
                    return true;
                }

                // Check if module is core (always accessible)
                $moduleRecord = \DB::table('modules')
                    ->where('slug', $module)
                    ->first();

                if ($moduleRecord && $moduleRecord->type === 'core') {
                    return true;
                }

                // Check if plan includes elective modules
                $subscription = $this->getSubscriptionStatus($organizationId);
                if ($subscription && $subscription['has_elective_modules'] && $moduleRecord?->type === 'elective') {
                    return true;
                }

                return false;
            } catch (\Exception $e) {
                Log::warning('Failed to check module access', [
                    'organization_id' => $organizationId,
                    'module' => $module,
                    'error' => $e->getMessage(),
                ]);
                return true; // Fail open
            }
        });
    }

    /**
     * Handle no subscription case
     */
    protected function handleNoSubscription(Request $request, $organization): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'subscription_required',
                'message' => 'Your organization does not have an active Addy subscription.',
                'upgrade_url' => "{$this->pendaUrl}/org/{$organization->id}/subscriptions",
            ], 403);
        }

        // Redirect to upgrade page
        return redirect()->route('subscription.required')
            ->with('message', 'Please subscribe to Addy to continue.');
    }

    /**
     * Handle no module access case
     */
    protected function handleNoModuleAccess(Request $request, string $module, array $subscription): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'module_access_required',
                'message' => "This feature requires the '{$module}' module.",
                'current_plan' => $subscription['plan'] ?? 'unknown',
                'upgrade_url' => "{$this->pendaUrl}/pricing",
            ], 403);
        }

        return redirect()->route('subscription.upgrade')
            ->with('module', $module)
            ->with('message', "Upgrade your plan to access the {$module} module.");
    }

    /**
     * Get current organization from request
     */
    protected function getCurrentOrganization(Request $request)
    {
        // Try session first
        $orgId = session('current_organization_id');
        
        if ($orgId) {
            return (object) ['id' => $orgId];
        }

        // Try from authenticated user
        $user = $request->user();
        if ($user && method_exists($user, 'currentOrganization')) {
            return $user->currentOrganization();
        }

        return null;
    }

    /**
     * Get service token for API calls
     */
    protected function getServiceToken(): ?string
    {
        // This would be a service-to-service JWT token
        // For now, return null and let the API handle it
        return config('services.penda_sso.service_token');
    }

    /**
     * Clear cached subscription for organization
     */
    public static function clearCache(string $organizationId): void
    {
        Cache::forget("penda_subscription_addy_{$organizationId}");
    }
}



