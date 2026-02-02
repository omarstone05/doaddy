<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\AddyCulturalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $organization = Organization::findOrFail($user->organization_id);

        $organization->logo_url = ($organization->logo && Storage::disk('public')->exists($organization->logo))
            ? Storage::disk('public')->url($organization->logo)
            : null;

        // Access Control / Team section - Users with roles
        $organizationUsers = null;
        $organizationRoles = null;
        $teamViewMode = null;
        $currentUserRole = $user->getOrganizationRole($user->organization_id);
        
        // Check if we're on a team route (Access Control)
        $path = parse_url($request->url(), PHP_URL_PATH);
        $isTeamRoute = str_contains($path, '/settings/team') || str_contains($path, '/team');
        
        if ($isTeamRoute) {
            $teamViewMode = 'list';
            
            // Load all organization roles for the role selector
            $organizationRoles = \App\Models\OrganizationRole::orderBy('level', 'desc')->get();
            
            // Load users with access to this organization
            $query = $organization->members()
                ->withPivot('role', 'role_id', 'is_active', 'joined_at')
                ->with(['organizations' => function ($q) use ($organization) {
                    $q->where('organizations.id', $organization->id);
                }]);
            
            // Search filter
            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            // Role filter
            if ($request->has('role') && $request->role !== '') {
                $roleSlug = $request->role;
                $role = \App\Models\OrganizationRole::where('slug', $roleSlug)->first();
                if ($role) {
                    $query->wherePivot('role_id', $role->id);
                }
            }
            
            // Status filter
            if ($request->has('status') && $request->status !== '') {
                $query->wherePivot('is_active', $request->status === 'active');
            }
            
            $usersWithAccess = $query->orderBy('name')->paginate(20);
            
            // Transform users to include role information
            $organizationUsers = $usersWithAccess->through(function ($orgUser) use ($organization, $organizationRoles) {
                $pivot = $orgUser->organizations->first()?->pivot;
                $roleId = $pivot?->role_id;
                $role = $roleId ? $organizationRoles->firstWhere('id', $roleId) : null;
                
                // Fallback to role slug if role_id not set
                if (!$role && $pivot?->role) {
                    $role = $organizationRoles->firstWhere('slug', $pivot->role);
                }
                
                return [
                    'id' => $orgUser->id,
                    'name' => $orgUser->name,
                    'email' => $orgUser->email,
                    'avatar' => $orgUser->avatar,
                    'role' => $role ? [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'level' => $role->level,
                    ] : null,
                    'is_active' => $pivot?->is_active ?? true,
                    'joined_at' => $pivot?->joined_at,
                    'last_active_at' => $orgUser->last_active_at,
                ];
            });
        } else {
            $teamViewMode = null;
        }
        
        // Keep departments for potential HR module integration
        $departments = \App\Models\Department::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get modules
        $modules = $this->getModules($organization);

        // Get invoice settings
        $invoiceSettings = $this->getInvoiceSettings($organization);
        $bankDetails = $this->getBankDetails($organization);

        // Get Addy settings
        $addySettings = AddyCulturalSetting::where('organization_id', $organization->id)->first();
        
        // Get user pattern if the model exists
        $userPattern = null;
        if (class_exists('\App\Models\UserPattern')) {
            $userPattern = \App\Models\UserPattern::where('user_id', $user->id)->first();
        }

        // Get tax rates if Tax module exists
        $taxRates = [];
        if (class_exists('\App\Modules\Tax\Models\TaxRate')) {
            $taxRates = \App\Modules\Tax\Models\TaxRate::where('organization_id', $organization->id)
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get();
        }

        // Check if Digitax is available (Zambia-specific)
        $digitaxAvailable = $this->isDigitaxAvailable($organization);

        // Get DigiTax credentials if available
        $digitaxCredentials = $this->getDigitaxCredentials($organization);

        // Get gamification data
        $gamificationData = $this->getGamificationData($user, $organization);

        // Get subscription data
        $subscription = $this->getSubscriptionData($organization);

        return Inertia::render('Settings/Index', [
            'organization' => $organization,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'google_drive_connected' => !empty($user->google_drive_token),
                'google_drive_connected_at' => $user->google_drive_connected_at,
                'use_own_drive' => $user->use_own_drive ?? false,
            ],
            // Access Control data
            'organizationUsers' => $organizationUsers,
            'organizationRoles' => $organizationRoles,
            'currentUserRole' => $currentUserRole ? [
                'id' => $currentUserRole->id,
                'name' => $currentUserRole->name,
                'slug' => $currentUserRole->slug,
                'level' => $currentUserRole->level,
                'permissions' => $currentUserRole->permissions,
            ] : null,
            'teamViewMode' => $teamViewMode,
            'departments' => $departments,
            'filters' => $request->only(['role', 'status', 'search']),
            'modules' => $modules,
            'invoiceSettings' => $invoiceSettings,
            'bankDetails' => $bankDetails,
            'addySettings' => $addySettings,
            'userPattern' => $userPattern,
            'taxRates' => $taxRates,
            'digitaxAvailable' => $digitaxAvailable,
            'digitaxCredentials' => $digitaxCredentials,
            'gamificationData' => $gamificationData,
            'subscription' => $subscription,
        ]);
    }

    private function isDigitaxAvailable(Organization $organization): bool
    {
        // Digitax is only available for Zambia (ZMW currency)
        // You can expand this logic based on country/region
        return $organization->currency === 'ZMW' || 
               strtolower($organization->timezone ?? '') === 'africa/lusaka';
    }

    private function getDigitaxCredentials(Organization $organization): ?array
    {
        try {
            $credential = \App\Modules\SmartInvoice\Models\DigitaxCredential::where('organization_id', $organization->id)
                ->first();

            if (!$credential) {
                return null;
            }

            // Extract business info from test_result if available
            $testResult = $credential->test_result ?? [];
            
            // DigiTax uses "tax_pin" field name
            $tpin = $testResult['tax_pin'] ?? $testResult['tpin'] ?? $credential->api_secret ?? null;
            // Filter out placeholder values
            if ($tpin === 'pending') $tpin = null;
            
            $branchId = $testResult['id'] ?? $credential->api_key ?? null;
            if ($branchId === 'pending') $branchId = null;
            
            return [
                'id' => $credential->id,
                'environment' => $credential->environment,
                'is_active' => $credential->is_active,
                'last_tested_at' => $credential->last_tested_at?->toIso8601String(),
                'has_api_key' => !empty($credential->digitax_api_key),
                // Business details from DigiTax /info response
                'tpin' => $tpin,
                'branch_id' => $branchId,
                'branch_office_id' => $testResult['branch_office_id'] ?? null,
                'is_head_office' => $testResult['is_head_office'] ?? false,
                'is_test' => $testResult['is_test'] ?? false,
                'is_live' => $testResult['is_live'] ?? false,
                // Status
                'status' => $credential->is_active ? 'connected' : ($credential->test_error ? 'error' : 'pending'),
                'test_error' => $credential->test_error,
            ];
        } catch (\Exception $e) {
            \Log::warning('Failed to get DigiTax credentials', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function getModules(Organization $organization): array
    {
        try {
            $moduleManager = app(\App\Support\ModuleManager::class);
            $allModules = $moduleManager->all();
            
            $modules = [];
            foreach ($allModules as $name => $module) {
                // Hide Tax module from toggle list - tax rates are always available in Settings
                if ($name === 'Tax') {
                    continue;
                }
                
                $modules[] = [
                    'name' => $name,
                    'display_name' => $module['config']['name'] ?? $name,
                    'description' => $module['config']['description'] ?? '',
                    'version' => $module['version'] ?? '1.0.0',
                    'author' => $module['config']['author'] ?? 'Unknown',
                    'features' => $module['config']['features'] ?? [],
                    'suitable_for' => $module['config']['suitable_for'] ?? [],
                    'dependencies' => $module['config']['dependencies'] ?? [],
                    // Use isEnabled() to check organization's enabled_modules, not module.json config
                    'enabled' => $moduleManager->isEnabled($name),
                ];
            }
            
            return $modules;
        } catch (\Exception $e) {
            \Log::error('Failed to get modules', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getInvoiceSettings(Organization $organization): ?array
    {
        $settings = $organization->settings ?? [];
        $invoiceSettings = $settings['invoice'] ?? [];
        
        return [
            'company_name' => $invoiceSettings['company_name'] ?? $organization->name,
            'company_address' => $invoiceSettings['company_address'] ?? '',
            'company_city' => $invoiceSettings['company_city'] ?? '',
            'company_phone' => $invoiceSettings['company_phone'] ?? '',
            'company_email' => $invoiceSettings['company_email'] ?? '',
            'company_tax_id' => $invoiceSettings['company_tax_id'] ?? '',
            'invoice_prefix' => $invoiceSettings['invoice_prefix'] ?? 'INV',
            'quote_prefix' => $invoiceSettings['quote_prefix'] ?? 'QUO',
            'default_due_days' => $invoiceSettings['default_due_days'] ?? 30,
            'quote_validity_days' => $invoiceSettings['quote_validity_days'] ?? 30,
            'invoice_notes' => $invoiceSettings['invoice_notes'] ?? '',
            'invoice_terms' => $invoiceSettings['invoice_terms'] ?? '',
        ];
    }

    private function getBankDetails(Organization $organization): ?array
    {
        $settings = $organization->settings ?? [];
        $bankDetails = $settings['bank_details'] ?? [];
        
        return [
            'bank_name' => $bankDetails['bank_name'] ?? '',
            'account_name' => $bankDetails['account_name'] ?? '',
            'account_number' => $bankDetails['account_number'] ?? '',
            'branch' => $bankDetails['branch'] ?? '',
            'swift_code' => $bankDetails['swift_code'] ?? '',
        ];
    }

    public function updateLogo(Request $request)
    {
        try {
            $organization = Organization::findOrFail(Auth::user()->organization_id);

            $validated = $request->validate([
                'logo' => 'required|image|mimes:jpeg,jpg,png,gif,svg|max:2048', // 2MB max
            ]);

            try {
                $logoFile = $request->file('logo');
                
                \Log::info('Logo upload attempt', [
                    'organization_id' => $organization->id,
                    'file_name' => $logoFile->getClientOriginalName(),
                    'file_size' => $logoFile->getSize(),
                    'mime_type' => $logoFile->getMimeType(),
                ]);

                // Delete old logo if exists
                if ($organization->logo && Storage::disk('public')->exists($organization->logo)) {
                    Storage::disk('public')->delete($organization->logo);
                    \Log::info('Deleted old logo', ['old_logo_path' => $organization->logo]);
                }

                // Ensure directory exists
                $logoDir = "logos/organizations/{$organization->id}";
                if (!Storage::disk('public')->exists($logoDir)) {
                    Storage::disk('public')->makeDirectory($logoDir, 0755, true);
                }

                // Store new logo
                $logoPath = $logoFile->store($logoDir, 'public');
                $organization->logo = $logoPath;
                $organization->save();
                
                \Log::info('Logo uploaded successfully', [
                    'logo_path' => $logoPath,
                    'full_path' => Storage::disk('public')->path($logoPath),
                ]);

                try {
                    return $this->notifyAndBack('success', 'Logo Updated', 'Your organization logo has been updated successfully.');
                } catch (\Exception $e) {
                    \Log::warning('Failed to create notification for logo update', [
                        'error' => $e->getMessage(),
                    ]);
                    return back()->with('message', 'Logo updated successfully');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to upload logo', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'organization_id' => $organization->id,
                ]);
                
                return back()->withErrors([
                    'logo' => 'Failed to upload logo: ' . $e->getMessage(),
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to update logo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors([
                'logo' => 'Failed to update logo. Please try again or contact support if the problem persists.',
            ]);
        }
    }

    public function update(Request $request)
    {
        try {
            $organization = Organization::findOrFail(Auth::user()->organization_id);

            if ($request->filled('slug')) {
                $request->merge([
                    'slug' => Str::slug($request->input('slug')),
                ]);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:organizations,slug,' . $organization->id,
                'business_type' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'tone_preference' => 'nullable|in:professional,casual,motivational,sassy,technical,formal,conversational,friendly',
                'currency' => 'nullable|string|size:3',
                'timezone' => 'nullable|string|max:255',
            ]);

            // Convert empty strings to null for nullable fields
            $nullableFields = ['slug', 'business_type', 'industry', 'currency', 'timezone'];
            foreach ($nullableFields as $field) {
                if (isset($validated[$field]) && $validated[$field] === '') {
                    $validated[$field] = null;
                }
            }
            
            // Handle tone_preference - if empty string, keep existing value (don't update)
            // If a valid value is provided, it will be saved
            if (isset($validated['tone_preference']) && $validated['tone_preference'] === '') {
                // Don't change tone_preference if empty string is sent
                unset($validated['tone_preference']);
            } elseif (isset($validated['tone_preference'])) {
                // Ensure tone_preference is saved if provided
                \Log::info('Saving tone_preference', [
                    'organization_id' => $organization->id,
                    'old_tone' => $organization->tone_preference,
                    'new_tone' => $validated['tone_preference'],
                ]);
            }

            if (!array_key_exists('slug', $validated) || $validated['slug'] === null || $validated['slug'] === '') {
                if ($organization->slug) {
                    $validated['slug'] = $organization->slug;
                } else {
                    $validated['slug'] = $this->generateUniqueSlug($validated['name'], $organization->id);
                }
            }

            // Logo is handled separately via updateLogo endpoint

            // Log what we're about to update
            \Log::info('Updating organization settings', [
                'organization_id' => $organization->id,
                'fields_to_update' => array_keys($validated),
                'tone_preference_included' => isset($validated['tone_preference']),
                'tone_preference_value' => $validated['tone_preference'] ?? 'not set',
            ]);

            $organization->update($validated);
            
            // Verify tone_preference was saved
            $organization->refresh();
            \Log::info('Organization settings updated', [
                'organization_id' => $organization->id,
                'current_tone_preference' => $organization->tone_preference,
            ]);
            
            // Sync tone to AddyCulturalSetting
            $this->syncAddyToneSetting($organization);

            // Try to create notification, but don't fail if it doesn't work
            try {
                return $this->notifyAndBack('success', 'Settings Updated', 'Your organization settings have been updated successfully.');
            } catch (\Exception $e) {
                \Log::warning('Failed to create notification for settings update', [
                    'error' => $e->getMessage(),
                ]);
                // Still return success even if notification fails
                return back()->with('message', 'Settings updated successfully');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to update settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to update settings. Please try again or contact support if the problem persists.',
            ])->withInput($request->except(['password', 'password_confirmation', 'logo']));
        }
    }

    private function generateUniqueSlug(string $name, string $organizationId): string
    {
        $baseSlug = Str::slug($name);

        if (!$baseSlug) {
            $baseSlug = Str::lower(Str::random(8));
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Organization::where('slug', $slug)
                ->where('id', '!=', $organizationId)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function updateDrivePreference(Request $request)
    {
        $validated = $request->validate([
            'use_own_drive' => 'required|boolean',
        ]);

        $user = Auth::user();
        $user->update([
            'use_own_drive' => $validated['use_own_drive'],
        ]);

        return back()->with('message', 'Drive preference updated successfully');
    }

    public function disconnectDrive()
    {
        $user = Auth::user();
        $user->update([
            'google_drive_token' => null,
            'google_drive_connected_at' => null,
            'use_own_drive' => false,
        ]);

        return back()->with('message', 'Google Drive disconnected successfully');
    }

    private function getGamificationData($user, Organization $organization): array
    {
        $xpTotal = \App\Models\GamificationXP::where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->sum('xp_amount');

        $badges = \App\Models\GamificationBadge::where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->orderBy('earned_at', 'desc')
            ->get();

        $streak = \App\Models\GamificationStreak::where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->first();

        // Calculate level from XP
        $xpPerLevel = config('gamification.xp_per_level', 100);
        $level = (int) floor($xpTotal / $xpPerLevel) + 1;
        $xpForNextLevel = $level * $xpPerLevel;
        $xpProgress = $xpTotal % $xpPerLevel;

        // Get level title
        $levelTitles = config('gamification.level_titles', []);
        $levelTitle = 'Emerging Business';
        foreach ($levelTitles as $lvl => $title) {
            if ($level >= $lvl) {
                $levelTitle = $title;
            }
        }

        // Get all available badges from config
        $availableBadges = config('gamification.badges', []);
        $earnedBadgeTypes = $badges->pluck('badge_type')->toArray();

        // Get recent XP history
        $recentXP = \App\Models\GamificationXP::where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // Get organization-wide leaderboard (top 10)
        $leaderboard = \App\Models\GamificationXP::where('organization_id', $organization->id)
            ->selectRaw('user_id, SUM(xp_amount) as total_xp')
            ->groupBy('user_id')
            ->orderByDesc('total_xp')
            ->take(10)
            ->with('user:id,name,email')
            ->get()
            ->map(function ($entry, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $entry->user_id,
                    'name' => $entry->user->name ?? 'Unknown',
                    'total_xp' => (int) $entry->total_xp,
                    'level' => (int) floor($entry->total_xp / 100) + 1,
                ];
            });

        return [
            'xp_total' => (int) $xpTotal,
            'level' => $level,
            'level_title' => $levelTitle,
            'xp_for_next_level' => $xpForNextLevel,
            'xp_progress' => $xpProgress,
            'xp_progress_percent' => $xpPerLevel > 0 ? round(($xpProgress / $xpPerLevel) * 100) : 0,
            'badges' => $badges,
            'earned_badge_types' => $earnedBadgeTypes,
            'available_badges' => $availableBadges,
            'streak' => $streak ? [
                'current' => $streak->current_streak ?? 0,
                'longest' => $streak->longest_streak ?? 0,
                'last_activity' => $streak->last_activity_at,
            ] : ['current' => 0, 'longest' => 0, 'last_activity' => null],
            'recent_xp' => $recentXP,
            'leaderboard' => $leaderboard,
            'xp_rewards' => config('gamification.xp_rewards', []),
        ];
    }

    private function syncAddyToneSetting(Organization $organization): void
    {
        if (!$organization->tone_preference) {
            \Log::info('No tone_preference to sync', [
                'organization_id' => $organization->id,
            ]);
            return;
        }

        $setting = AddyCulturalSetting::updateOrCreate(
            ['organization_id' => $organization->id],
            ['tone' => $organization->tone_preference]
        );

        \Log::info('Synced tone_preference to AddyCulturalSetting', [
            'organization_id' => $organization->id,
            'tone' => $organization->tone_preference,
            'setting_id' => $setting->id,
        ]);
    }

    private function getSubscriptionData(Organization $organization): ?array
    {
        try {
            $subscription = \DB::table('organization_subscriptions')
                ->where('organization_id', $organization->id)
                ->where('app_id', 'addy')
                ->whereIn('status', ['active', 'trial'])
                ->first();

            if (!$subscription) {
                return null;
            }

            // Calculate days remaining
            $daysRemaining = null;
            $expiryDate = null;
            if ($subscription->status === 'trial' && $subscription->expires_at) {
                $expiryDate = \Carbon\Carbon::parse($subscription->expires_at);
                $daysRemaining = max(0, now()->diffInDays($expiryDate, false));
            } elseif ($subscription->expires_at) {
                $expiryDate = \Carbon\Carbon::parse($subscription->expires_at);
                $daysRemaining = max(0, now()->diffInDays($expiryDate, false));
            }

            // Plan display names
            $planNames = [
                'basic' => 'Basic',
                'starter' => 'Starter',
                'professional' => 'Professional',
                'enterprise' => 'Enterprise',
            ];

            // Plan features
            $planFeatures = [
                'basic' => [
                    'Unlimited invoices & quotes',
                    'Full reporting & analytics',
                    'Your whole team included',
                    'Addy AI assistant',
                    'Email support',
                ],
                'starter' => [
                    'Unlimited invoices & quotes',
                    'Full reporting & analytics',
                    'Your whole team included',
                    'Addy AI assistant',
                    'Email support',
                ],
                'professional' => [
                    'Everything in Basic',
                    'Cash flow insights & forecasting',
                    'Custom modules',
                    'Advanced reporting',
                    'Priority support',
                ],
                'enterprise' => [
                    'Everything in Professional',
                    'Access control & permissions',
                    'Full audit trails',
                    'Access to other Penda apps',
                    'Dedicated success manager',
                ],
            ];

            $plan = $subscription->plan ?? 'basic';

            return [
                'id' => $subscription->id,
                'plan' => $plan,
                'plan_name' => $planNames[$plan] ?? ucfirst($plan),
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at,
                'expires_at' => $subscription->expires_at,
                'days_remaining' => $daysRemaining,
                'has_elective_modules' => (bool) $subscription->has_elective_modules,
                'has_custom_module' => (bool) $subscription->has_custom_module,
                'max_users' => $subscription->max_users,
                'features' => $planFeatures[$plan] ?? $planFeatures['basic'],
                'is_trial' => $subscription->status === 'trial',
                'manage_url' => config('services.penda.url', 'https://penda.cloud') . '/org/' . $organization->id . '/subscriptions',
            ];
        } catch (\Exception $e) {
            \Log::warning('Failed to get subscription data', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
