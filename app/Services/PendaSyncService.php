<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PendaSyncService
{
    protected string $pendaUrl;
    protected string $appId = 'addy';

    public function __construct()
    {
        $this->pendaUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
    }

    /**
     * Sync subscription data for an organization from Penda Cloud
     */
    public function syncSubscription(string $organizationId): bool
    {
        try {
            $response = Http::timeout(10)
                ->withToken($this->getServiceToken())
                ->get("{$this->pendaUrl}/api/subscriptions/organization/{$organizationId}");

            if (!$response->successful()) {
                Log::warning('Failed to sync subscription from Penda', [
                    'organization_id' => $organizationId,
                    'status' => $response->status(),
                ]);
                return false;
            }

            $data = $response->json();
            
            // Find subscription for this app
            $subscription = collect($data['subscriptions'] ?? [])
                ->firstWhere('app.slug', $this->appId);

            if ($subscription) {
                DB::table('organization_subscriptions')->updateOrInsert(
                    [
                        'organization_id' => $organizationId,
                        'app_id' => $this->appId,
                    ],
                    [
                        'plan' => $subscription['plan']['slug'] ?? 'free',
                        'status' => $subscription['status'] ?? 'active',
                        'has_elective_modules' => $subscription['plan']['has_elective_modules'] ?? false,
                        'has_custom_module' => $subscription['plan']['has_custom_module'] ?? false,
                        'max_users' => $subscription['plan']['max_users'] ?? null,
                        'starts_at' => $subscription['starts_at'] ?? null,
                        'expires_at' => $subscription['ends_at'] ?? null,
                        'synced_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            } else {
                // No subscription found, remove local record
                DB::table('organization_subscriptions')
                    ->where('organization_id', $organizationId)
                    ->where('app_id', $this->appId)
                    ->delete();
            }

            // Sync modules
            $modules = collect($data['modules'] ?? []);
            foreach ($modules as $module) {
                DB::table('organization_modules')->updateOrInsert(
                    [
                        'organization_id' => $organizationId,
                        'module_slug' => $module['slug'],
                    ],
                    [
                        'access_type' => $module['access_type'] ?? 'subscription',
                        'is_active' => $module['is_active'] ?? true,
                        'expires_at' => $module['expires_at'] ?? null,
                        'synced_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            Log::info('Synced subscription from Penda', [
                'organization_id' => $organizationId,
                'has_subscription' => (bool) $subscription,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error syncing subscription from Penda', [
                'organization_id' => $organizationId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync branding data for an organization from Penda Cloud
     */
    public function syncBranding(string $organizationId): bool
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->pendaUrl}/api/organizations/{$organizationId}/branding");

            if (!$response->successful()) {
                Log::warning('Failed to sync branding from Penda', [
                    'organization_id' => $organizationId,
                    'status' => $response->status(),
                ]);
                return false;
            }

            $data = $response->json();

            DB::table('organization_branding_cache')->updateOrInsert(
                ['organization_id' => $organizationId],
                [
                    'name' => $data['name'] ?? null,
                    'logo' => $data['branding']['logo'] ?? null,
                    'logo_light' => $data['branding']['logo_light'] ?? null,
                    'logo_dark' => $data['branding']['logo_dark'] ?? null,
                    'favicon' => $data['branding']['favicon'] ?? null,
                    'primary_color' => $data['branding']['primary_color'] ?? null,
                    'secondary_color' => $data['branding']['secondary_color'] ?? null,
                    'email' => $data['contact']['email'] ?? null,
                    'phone' => $data['contact']['phone'] ?? null,
                    'website' => $data['contact']['website'] ?? null,
                    'address' => $data['address']['full'] ?? null,
                    'tax_id' => $data['legal']['tax_id'] ?? null,
                    'registration_number' => $data['legal']['registration_number'] ?? null,
                    'social_links' => json_encode($data['social_links'] ?? []),
                    'synced_at' => now(),
                    'updated_at' => now(),
                ]
            );

            Log::info('Synced branding from Penda', ['organization_id' => $organizationId]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error syncing branding from Penda', [
                'organization_id' => $organizationId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync all organizations' data
     */
    public function syncAllOrganizations(): int
    {
        $synced = 0;
        
        // Get all organization IDs from local database
        $organizationIds = DB::table('organizations')
            ->pluck('id')
            ->toArray();

        foreach ($organizationIds as $orgId) {
            if ($this->syncSubscription($orgId)) {
                $synced++;
            }
            $this->syncBranding($orgId);
            
            // Small delay to avoid rate limiting
            usleep(100000); // 100ms
        }

        return $synced;
    }

    /**
     * Sync modules registry from Penda Cloud
     */
    public function syncModulesRegistry(): int
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->pendaUrl}/api/apps/{$this->appId}/modules");

            if (!$response->successful()) {
                Log::warning('Failed to sync modules registry from Penda');
                return 0;
            }

            $modules = $response->json('modules') ?? [];
            $synced = 0;

            foreach ($modules as $module) {
                DB::table('modules')->updateOrInsert(
                    ['slug' => $module['slug']],
                    [
                        'name' => $module['name'],
                        'type' => $module['type'],
                        'description' => $module['description'] ?? null,
                        'icon' => $module['icon'] ?? null,
                        'price_monthly' => $module['price_monthly'] ?? null,
                        'is_active' => $module['is_active'] ?? true,
                        'updated_at' => now(),
                    ]
                );
                $synced++;
            }

            return $synced;
        } catch (\Exception $e) {
            Log::error('Error syncing modules registry', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get service token for authenticated API calls
     */
    protected function getServiceToken(): ?string
    {
        return config('services.penda_sso.service_token');
    }
}





