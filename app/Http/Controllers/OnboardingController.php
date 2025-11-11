<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function show()
    {
        // Check if user has already completed onboarding
        $user = Auth::user();
        if ($user && $user->organization) {
            // Check if organization has been configured
            $org = $user->organization;
            // Check if all required onboarding fields are set
            if ($org->industry && $org->currency && $org->tone_preference && $org->industry !== 'retail') {
                // If industry is set to something other than default, onboarding is complete
                return redirect()->route('dashboard');
            }
        }

        return Inertia::render('Onboarding/Conversation', [
            'user' => $user,
            'organization' => $user->organization ?? null,
        ]);
    }

    public function complete(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'currency' => 'required|string|size:3',
            'tone_preference' => 'required|in:formal,conversational,technical',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Update user name if provided
        if (isset($validated['name'])) {
            $user->update(['name' => $validated['name']]);
        }

        // Update organization with onboarding data
        if ($user->organization) {
            $user->organization->update([
                'name' => $validated['business_name'],
                'slug' => Str::slug($validated['business_name']),
                'industry' => $validated['industry'],
                'currency' => $validated['currency'],
                'tone_preference' => $validated['tone_preference'],
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Welcome to Addy! Let\'s get started.');
    }
}

