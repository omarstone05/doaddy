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

        // Get team members if on team tab
        $teamMembers = null;
        $departments = null;
        $teamMember = null;
        $organizationRoles = null;
        $userRole = null;
        $users = null;
        $teamViewMode = null;
        
        // Always load departments for team forms
        $departments = \App\Models\Department::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Check if we're on a team route
        $path = parse_url($request->url(), PHP_URL_PATH);
        $isTeamRoute = str_contains($path, '/settings/team') || str_contains($path, '/team');
        
        if ($isTeamRoute) {
            $teamViewMode = 'list';
            
            if (preg_match('/\/settings\/team\/([^\/]+)\/edit$/', $path, $matches)) {
                $teamViewMode = 'edit';
                $teamMemberId = $matches[1];
                $teamMember = \App\Models\TeamMember::where('organization_id', $user->organization_id)
                    ->find($teamMemberId);
                
                if ($teamMember) {
                    $users = \App\Models\User::where('organization_id', $user->organization_id)
                        ->where(function ($q) use ($teamMember) {
                            $q->whereDoesntHave('teamMember')
                              ->orWhereHas('teamMember', function ($q) use ($teamMember) {
                                  $q->where('id', $teamMember->id);
                              });
                        })
                        ->orderBy('name')
                        ->get();
                }
            } elseif (preg_match('/\/settings\/team\/create$/', $path)) {
                $teamViewMode = 'create';
                $users = \App\Models\User::where('organization_id', $user->organization_id)
                    ->whereDoesntHave('teamMember')
                    ->orderBy('name')
                    ->get();
            } elseif (preg_match('/\/settings\/team\/([^\/]+)$/', $path, $matches)) {
                $teamViewMode = 'show';
                $teamMemberId = $matches[1];
                $teamMember = \App\Models\TeamMember::where('organization_id', $user->organization_id)
                    ->with(['user', 'department', 'sales', 'attachments.uploadedBy', 'documents.createdBy', 'documents.attachments'])
                    ->find($teamMemberId);
                
                if ($teamMember && $teamMember->user) {
                    $organizationRoles = \App\Models\OrganizationRole::orderBy('level', 'desc')->get();
                    $userRole = $teamMember->user->getOrganizationRole($user->organization_id);
                }
            } else {
                // Default list view
                $teamViewMode = 'list';
                
                // Check if current user has a TeamMember record, if not create one
                $userTeamMember = \App\Models\TeamMember::where('organization_id', $user->organization_id)
                    ->where('user_id', $user->id)
                    ->first();
                
                if (!$userTeamMember) {
                    // Auto-create a TeamMember record for the current user
                    $nameParts = explode(' ', $user->name, 2);
                    $firstName = $nameParts[0] ?? 'User';
                    $lastName = $nameParts[1] ?? '';
                    
                    \App\Models\TeamMember::create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'organization_id' => $user->organization_id,
                        'user_id' => $user->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $user->email,
                        'is_active' => true,
                    ]);
                    
                    \Log::info('Auto-created TeamMember for current user', [
                        'user_id' => $user->id,
                        'organization_id' => $user->organization_id,
                    ]);
                }
                
                $query = \App\Models\TeamMember::where('organization_id', $user->organization_id);

                if ($request->has('department_id') && $request->department_id !== '') {
                    $query->where('department_id', $request->department_id);
                }

                if ($request->has('is_active') && $request->is_active !== '') {
                    $query->where('is_active', $request->is_active === 'true');
                }

                if ($request->has('search') && $request->search !== '') {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('employee_number', 'like', "%{$search}%");
                    });
                }

                $teamMembers = $query->with(['user', 'department'])->orderBy('first_name')->paginate(20);
            }
        } else {
            // Not on team route, but ensure teamViewMode is null
            $teamViewMode = null;
        }

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

        return Inertia::render('Settings/Index', [
            'organization' => $organization,
            'user' => [
                'google_drive_connected' => !empty($user->google_drive_token),
                'google_drive_connected_at' => $user->google_drive_connected_at,
                'use_own_drive' => $user->use_own_drive ?? false,
            ],
            'teamMembers' => $teamMembers,
            'teamMember' => $teamMember,
            'teamViewMode' => $teamViewMode,
            'departments' => $departments,
            'users' => $users,
            'organizationRoles' => $organizationRoles,
            'userRole' => $userRole,
            'filters' => $request->only(['department_id', 'is_active', 'search']),
            'modules' => $modules,
            'invoiceSettings' => $invoiceSettings,
            'bankDetails' => $bankDetails,
            'addySettings' => $addySettings,
            'userPattern' => $userPattern,
            'taxRates' => $taxRates,
            'digitaxAvailable' => $digitaxAvailable,
            'digitaxCredentials' => $digitaxCredentials,
            'gamificationData' => $gamificationData,
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
            $credential = \Addy\Modules\SmartInvoice\Models\DigitaxCredential::where('organization_id', $organization->id)
                ->first();

            if (!$credential) {
                return null;
            }

            // Extract business info from test_result if available
            $testResult = $credential->test_result ?? [];
            
            return [
                'id' => $credential->id,
                'environment' => $credential->environment,
                'is_active' => $credential->is_active,
                'last_tested_at' => $credential->last_tested_at?->toIso8601String(),
                'has_api_key' => !empty($credential->digitax_api_key),
                // Business details from DigiTax /info response
                'tpin' => $testResult['tpin'] ?? $credential->api_secret ?? null,
                'business_name' => $testResult['business_name'] ?? $testResult['name'] ?? null,
                'serial_number' => $testResult['serial_number'] ?? $credential->api_key ?? null,
                'device_id' => $testResult['device_id'] ?? null,
                'branch_name' => $testResult['branch_name'] ?? null,
                'address' => $testResult['address'] ?? null,
                'phone' => $testResult['phone'] ?? null,
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
                    'enabled' => $module['enabled'] ?? false,
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
}
