<?php

namespace App\Http\Controllers;

use App\Models\Organization;
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
        
        // Add logo URL if logo exists
        $organization->logo_url = $organization->logo 
            ? Storage::url($organization->logo) 
            : null;

        return Inertia::render('Settings/Index', [
            'organization' => $organization,
        ]);
    }

    public function update(Request $request)
    {
        try {
            $organization = Organization::findOrFail(Auth::user()->organization_id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:organizations,slug,' . $organization->id,
                'business_type' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'tone_preference' => 'nullable|in:professional,casual,motivational,sassy,technical,formal,conversational,friendly',
                'currency' => 'nullable|string|size:3',
                'timezone' => 'nullable|string|max:255',
                'logo' => 'nullable|image|mimes:jpeg,jpg,png,gif,svg|max:2048', // 2MB max
            ]);

            // Convert empty strings to null for nullable fields
            $nullableFields = ['slug', 'business_type', 'industry', 'currency', 'timezone'];
            foreach ($nullableFields as $field) {
                if (isset($validated[$field]) && $validated[$field] === '') {
                    $validated[$field] = null;
                }
            }
            
            // Handle tone_preference separately - only set if provided and valid
            if (isset($validated['tone_preference']) && $validated['tone_preference'] === '') {
                // Don't change tone_preference if empty string is sent
                unset($validated['tone_preference']);
            }

            // Handle logo upload
            if ($request->hasFile('logo')) {
                try {
                    // Delete old logo if exists
                    if ($organization->logo && Storage::disk('public')->exists($organization->logo)) {
                        Storage::disk('public')->delete($organization->logo);
                    }

                    // Store new logo
                    $logoPath = $request->file('logo')->store("logos/organizations/{$organization->id}", 'public');
                    $validated['logo'] = $logoPath;
                } catch (\Exception $e) {
                    \Log::error('Failed to upload logo', [
                        'error' => $e->getMessage(),
                        'organization_id' => $organization->id,
                    ]);
                    // Continue without logo if upload fails
                    unset($validated['logo']);
                }
            } else {
                // Keep existing logo if no new one uploaded
                unset($validated['logo']);
            }

            $organization->update($validated);

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
}
