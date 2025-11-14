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
    public function index()
    {
        $organization = Organization::findOrFail(Auth::user()->organization_id);

        $organization->logo_url = ($organization->logo && Storage::disk('public')->exists($organization->logo))
            ? Storage::disk('public')->url($organization->logo)
            : null;

        return Inertia::render('Settings/Index', [
            'organization' => $organization,
        ]);
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
