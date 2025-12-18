<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PendaBrandingService
{
    protected string $pendaUrl;
    protected int $cacheTtl = 3600; // 1 hour cache

    public function __construct()
    {
        $this->pendaUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
    }

    /**
     * Get organization branding from Penda Cloud
     */
    public function getForOrganization(string $organizationId): ?array
    {
        $cacheKey = "penda_branding_{$organizationId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($organizationId) {
            try {
                $response = Http::timeout(5)
                    ->get("{$this->pendaUrl}/api/organizations/{$organizationId}/branding");

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Failed to fetch Penda branding', [
                    'organization_id' => $organizationId,
                    'status' => $response->status(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Error fetching Penda branding', [
                    'organization_id' => $organizationId,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Get organization logo URL
     */
    public function getLogo(string $organizationId, string $context = 'default'): ?string
    {
        $branding = $this->getForOrganization($organizationId);
        
        if (!$branding || !isset($branding['branding'])) {
            return null;
        }

        $logos = $branding['branding'];
        
        return match ($context) {
            'light' => $logos['logo_light'] ?? $logos['logo'] ?? null,
            'dark' => $logos['logo_dark'] ?? $logos['logo'] ?? null,
            default => $logos['logo'] ?? $logos['logo_light'] ?? $logos['logo_dark'] ?? null,
        };
    }

    /**
     * Get brand colors
     */
    public function getColors(string $organizationId): array
    {
        $branding = $this->getForOrganization($organizationId);
        
        return [
            'primary' => $branding['branding']['primary_color'] ?? '#0d9488',
            'secondary' => $branding['branding']['secondary_color'] ?? '#14b8a6',
        ];
    }

    /**
     * Get full address string
     */
    public function getAddress(string $organizationId): ?string
    {
        $branding = $this->getForOrganization($organizationId);
        
        return $branding['address']['full'] ?? null;
    }

    /**
     * Get contact info
     */
    public function getContact(string $organizationId): array
    {
        $branding = $this->getForOrganization($organizationId);
        
        return [
            'email' => $branding['contact']['email'] ?? null,
            'phone' => $branding['contact']['phone'] ?? null,
            'website' => $branding['contact']['website'] ?? null,
        ];
    }

    /**
     * Get legal info (tax ID, registration number)
     */
    public function getLegal(string $organizationId): array
    {
        $branding = $this->getForOrganization($organizationId);
        
        return [
            'tax_id' => $branding['legal']['tax_id'] ?? null,
            'registration_number' => $branding['legal']['registration_number'] ?? null,
        ];
    }

    /**
     * Clear cached branding for an organization
     */
    public function clearCache(string $organizationId): void
    {
        Cache::forget("penda_branding_{$organizationId}");
    }

    /**
     * Bulk sync branding for multiple organizations
     */
    public function syncMultiple(array $organizationIds): array
    {
        try {
            $response = Http::timeout(10)
                ->post("{$this->pendaUrl}/api/organizations/sync-branding", [
                    'organization_ids' => $organizationIds,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Cache each organization's branding
                foreach ($data['organizations'] ?? [] as $org) {
                    Cache::put(
                        "penda_branding_{$org['id']}",
                        $org,
                        $this->cacheTtl
                    );
                }

                return $data['organizations'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Error syncing Penda branding', ['error' => $e->getMessage()]);
            return [];
        }
    }
}





