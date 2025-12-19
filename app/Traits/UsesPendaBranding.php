<?php

namespace App\Traits;

use App\Services\PendaBrandingService;
use Illuminate\Support\Facades\DB;

trait UsesPendaBranding
{
    /**
     * Get organization branding for the current context
     */
    protected function getOrganizationBranding(?string $organizationId = null): array
    {
        $organizationId = $organizationId ?? session('current_organization_id');
        
        if (!$organizationId) {
            return $this->getDefaultBranding();
        }

        // Try local cache first
        $cached = DB::table('organization_branding_cache')
            ->where('organization_id', $organizationId)
            ->first();

        if ($cached) {
            return [
                'name' => $cached->name,
                'logo' => $cached->logo,
                'logo_light' => $cached->logo_light,
                'logo_dark' => $cached->logo_dark,
                'favicon' => $cached->favicon,
                'primary_color' => $cached->primary_color ?? '#0d9488',
                'secondary_color' => $cached->secondary_color ?? '#14b8a6',
                'email' => $cached->email,
                'phone' => $cached->phone,
                'website' => $cached->website,
                'address' => $cached->address,
                'tax_id' => $cached->tax_id,
                'registration_number' => $cached->registration_number,
                'social_links' => json_decode($cached->social_links ?? '[]', true),
            ];
        }

        // Fall back to Penda Cloud API
        $brandingService = app(PendaBrandingService::class);
        $branding = $brandingService->getForOrganization($organizationId);

        if ($branding) {
            return [
                'name' => $branding['name'] ?? null,
                'logo' => $branding['branding']['logo'] ?? null,
                'logo_light' => $branding['branding']['logo_light'] ?? null,
                'logo_dark' => $branding['branding']['logo_dark'] ?? null,
                'favicon' => $branding['branding']['favicon'] ?? null,
                'primary_color' => $branding['branding']['primary_color'] ?? '#0d9488',
                'secondary_color' => $branding['branding']['secondary_color'] ?? '#14b8a6',
                'email' => $branding['contact']['email'] ?? null,
                'phone' => $branding['contact']['phone'] ?? null,
                'website' => $branding['contact']['website'] ?? null,
                'address' => $branding['address']['full'] ?? null,
                'tax_id' => $branding['legal']['tax_id'] ?? null,
                'registration_number' => $branding['legal']['registration_number'] ?? null,
                'social_links' => $branding['social_links'] ?? [],
            ];
        }

        return $this->getDefaultBranding();
    }

    /**
     * Get the best logo for the given context
     */
    protected function getOrganizationLogo(?string $organizationId = null, string $context = 'default'): ?string
    {
        $branding = $this->getOrganizationBranding($organizationId);

        return match ($context) {
            'light' => $branding['logo_light'] ?? $branding['logo'] ?? null,
            'dark' => $branding['logo_dark'] ?? $branding['logo'] ?? null,
            default => $branding['logo'] ?? $branding['logo_light'] ?? $branding['logo_dark'] ?? null,
        };
    }

    /**
     * Get primary brand color
     */
    protected function getPrimaryColor(?string $organizationId = null): string
    {
        $branding = $this->getOrganizationBranding($organizationId);
        return $branding['primary_color'] ?? '#0d9488';
    }

    /**
     * Get organization contact info for documents
     */
    protected function getOrganizationContactInfo(?string $organizationId = null): array
    {
        $branding = $this->getOrganizationBranding($organizationId);
        
        return [
            'name' => $branding['name'] ?? config('app.name'),
            'email' => $branding['email'] ?? config('mail.from.address'),
            'phone' => $branding['phone'] ?? null,
            'website' => $branding['website'] ?? config('app.url'),
            'address' => $branding['address'] ?? null,
            'tax_id' => $branding['tax_id'] ?? null,
        ];
    }

    /**
     * Get default Addy branding
     */
    protected function getDefaultBranding(): array
    {
        return [
            'name' => config('app.name', 'Addy'),
            'logo' => asset('logo/addy-logo.png'),
            'logo_light' => asset('logo/addy-logo.png'),
            'logo_dark' => asset('logo/addy-logo-white.png'),
            'favicon' => asset('favicon.ico'),
            'primary_color' => '#0d9488',
            'secondary_color' => '#14b8a6',
            'email' => config('mail.from.address'),
            'phone' => null,
            'website' => config('app.url'),
            'address' => null,
            'tax_id' => null,
            'registration_number' => null,
            'social_links' => [],
        ];
    }
}






