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
        $nullableFields = ['slug', 'business_type', 'industry', 'tone_preference', 'currency', 'timezone'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($organization->logo && Storage::exists($organization->logo)) {
                Storage::delete($organization->logo);
            }

            // Store new logo
            $logoPath = $request->file('logo')->store("logos/organizations/{$organization->id}", 'public');
            $validated['logo'] = $logoPath;
        } else {
            // Keep existing logo if no new one uploaded
            unset($validated['logo']);
        }

        $organization->update($validated);

        return $this->notifyAndBack('success', 'Settings Updated', 'Your organization settings have been updated successfully.');
    }
}
