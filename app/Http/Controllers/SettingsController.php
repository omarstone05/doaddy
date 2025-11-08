<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $organization = Organization::findOrFail(Auth::user()->organization_id);

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
            'tone_preference' => 'nullable|string|max:255',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:255',
        ]);

        $organization->update($validated);

        return back()->with('message', 'Settings updated successfully');
    }
}

