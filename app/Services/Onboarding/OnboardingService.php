<?php

namespace App\Services\Onboarding;

use App\Models\Organization;
use App\Models\OnboardingSession;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Onboarding Service
 * Manages onboarding state and creates organizations
 */
class OnboardingService
{
    /**
     * Get user's onboarding session
     */
    public function getSession(string $userId): ?OnboardingSession
    {
        return OnboardingSession::where('user_id', $userId)
            ->where('completed', false)
            ->latest()
            ->first();
    }

    /**
     * Save onboarding progress
     */
    public function saveProgress(string $userId, string $phase, array $data): OnboardingSession
    {
        $session = $this->getSession($userId);

        if (!$session) {
            $session = OnboardingSession::create([
                'user_id' => $userId,
                'current_phase' => $phase,
                'data' => $data,
                'completed' => false,
            ]);
        } else {
            $session->update([
                'current_phase' => $phase,
                'data' => array_merge($session->data ?? [], $data),
            ]);
        }

        return $session;
    }

    /**
     * Complete onboarding and create organization
     */
    public function completeOnboarding(User $user, array $data): array
    {
        // Extract organization name from business description
        $organizationName = $this->extractBusinessName($data['business_description'] ?? $data['business_name'] ?? 'My Business');

        // Create or update organization
        $organization = $user->organization;
        
        if (!$organization) {
            $organization = Organization::create([
                'id' => Str::uuid(),
                'name' => $organizationName,
                'slug' => $this->generateUniqueSlug($organizationName),
                'owner_id' => $user->id,
            ]);
        }

        // Update organization with onboarding data
        $updateData = [
            'business_description' => $data['business_description'] ?? null,
            'business_category' => $data['confirmed_category'] ?? $data['industry'] ?? null,
            'team_size' => $data['team_size'] ?? null,
            'income_pattern' => $data['income_pattern'] ?? null,
            'priorities' => $data['priorities'] ?? [],
            'onboarding_completed_at' => now(),
        ];

        // Map confirmed_category to industry for backward compatibility
        if (isset($data['confirmed_category'])) {
            $updateData['industry'] = $this->mapCategoryToIndustry($data['confirmed_category']);
        } elseif (isset($data['industry'])) {
            $updateData['industry'] = $data['industry'];
        }

        // Add currency and tone if provided
        if (isset($data['currency'])) {
            $updateData['currency'] = $data['currency'];
        }
        if (isset($data['tone_preference'])) {
            $updateData['tone_preference'] = $data['tone_preference'];
        }

        $organization->update($updateData);

        // Attach user to organization if not already attached
        if (!$organization->members()->where('users.id', $user->id)->exists()) {
            $organization->members()->attach($user->id, [
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        // Set as user's current organization
        $user->update([
            'organization_id' => $organization->id, // Legacy field
        ]);
        
        // Set in session
        session(['current_organization_id' => $organization->id]);

        // Mark onboarding session as complete
        $session = $this->getSession($user->id);
        if ($session) {
            $session->update([
                'completed' => true,
                'completed_at' => now(),
            ]);
        }

        // Create default settings based on priorities
        $this->setupOrganizationDefaults($organization, $data);

        // Send welcome email if user has a valid email
        try {
            if ($user->email && !str_ends_with($user->email, '@whatsapp.addy')) {
                $emailService = app(\App\Services\Admin\EmailService::class);
                $emailService->sendWelcomeEmail($user, $organization);
            }
        } catch (\Exception $e) {
            // Log but don't fail onboarding if email fails
            \Log::warning('Failed to send welcome email during onboarding', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'organization' => $organization,
            'session' => $session,
        ];
    }

    /**
     * Extract business name from description
     */
    protected function extractBusinessName(string $description): string
    {
        $words = explode(' ', $description);
        
        // Take first 3-5 words as business name
        $name = implode(' ', array_slice($words, 0, min(4, count($words))));
        
        // Clean up
        $name = trim($name, '.,;:');
        
        // If too generic, use "My Business"
        $generic = ['we', 'i', 'the', 'a', 'an', 'our', 'my'];
        $firstWord = strtolower($words[0] ?? '');
        
        if (in_array($firstWord, $generic) && count($words) <= 3) {
            return 'My Business';
        }
        
        return ucwords($name);
    }

    /**
     * Generate unique slug
     */
    protected function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Map category to industry
     */
    protected function mapCategoryToIndustry(string $category): string
    {
        $mapping = [
            'Retail / Shop / Store' => 'retail',
            'Services & Consulting' => 'services',
            'Agriculture & Farming' => 'agriculture',
            'Hospitality & Food' => 'hospitality',
            'Construction & Engineering' => 'construction',
            'Education / Training' => 'education',
            'Health & Medical' => 'healthcare',
            'Transport & Logistics' => 'transport',
            'Technology & Software' => 'technology',
            'Beauty & Personal Care' => 'beauty',
            'Manufacturing & Production' => 'manufacturing',
            'Finance / Insurance' => 'finance',
            'Creative / Media' => 'creative',
            'NGO / Community Work' => 'ngo',
        ];

        return $mapping[$category] ?? 'other';
    }

    /**
     * Setup organization defaults based on priorities
     */
    protected function setupOrganizationDefaults(Organization $organization, array $data): void
    {
        $priorities = $data['priorities'] ?? [];

        // Enable modules based on priorities
        $moduleMap = [
            'finance' => ['finance', 'budgets'],
            'sales' => ['sales', 'invoices'],
            'projects' => ['projects', 'tasks', 'consulting'],
            'team' => ['team', 'hr'],
            'inventory' => ['inventory', 'products'],
            'approvals' => ['approvals', 'workflows'],
        ];

        $enabledModules = [];
        
        if (in_array('everything', $priorities)) {
            $enabledModules = ['finance', 'sales', 'projects', 'team', 'inventory', 'approvals', 'consulting'];
        } else {
            foreach ($priorities as $priority) {
                if (isset($moduleMap[$priority])) {
                    $enabledModules = array_merge($enabledModules, $moduleMap[$priority]);
                }
            }
        }

        // Save to organization settings
        $settings = $organization->settings ?? [];
        $settings['enabled_modules'] = array_unique($enabledModules);
        $organization->update(['settings' => $settings]);

        // Setup dashboard based on category
        $this->setupDashboard($organization, $data);
    }

    /**
     * Setup default dashboard based on category
     */
    protected function setupDashboard(Organization $organization, array $data): void
    {
        // Map categories to recommended dashboard cards
        $categoryDashboards = [
            'Retail / Shop / Store' => ['finance.revenue', 'finance.expenses', 'finance.profit', 'finance.cash_flow'],
            'Services & Consulting' => ['finance.revenue', 'consulting.active_projects', 'consulting.task_completion', 'finance.profit'],
            'Agriculture & Farming' => ['finance.revenue', 'finance.expenses', 'finance.profit', 'finance.monthly_goal'],
        ];

        $category = $data['confirmed_category'] ?? $data['business_category'] ?? null;
        $recommendedCards = $categoryDashboards[$category] ?? [
            'finance.revenue',
            'finance.expenses',
            'finance.profit',
            'finance.cash_flow',
        ];

        // Save recommended dashboard layout
        $settings = $organization->settings ?? [];
        $settings['recommended_dashboard_cards'] = $recommendedCards;
        $organization->update(['settings' => $settings]);
    }
}

