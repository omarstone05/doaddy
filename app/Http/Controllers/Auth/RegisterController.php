<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DashboardCard;
use App\Models\Organization;
use App\Models\OrgDashboardCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                function ($attribute, $value, $fail) {
                    // Check if password contains at least one number or special character
                    if (!preg_match('/\d/', $value) && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
                        $fail('The password must contain at least one number or special character (!@#$%^&*).');
                    }
                },
            ],
        ]);

        try {
            // Generate unique slug - allows duplicate company names but ensures unique slugs in DB
            // Multiple companies can have the same name, but each gets a unique slug (e.g., "my-company", "my-company-1", "my-company-2")
            $baseSlug = Str::slug($validated['organization_name']);
            $slug = $baseSlug;
            $counter = 1;
            
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $organization = Organization::create([
                'id' => (string) Str::uuid(),
                'name' => $validated['organization_name'], // Name can be duplicated
                'slug' => $slug, // Slug must be unique
                'tone_preference' => 'professional',
                'currency' => 'ZMW',
                'timezone' => 'Africa/Lusaka',
            ]);

            // Create default dashboard cards for the organization
            $this->createDefaultDashboardCards($organization->id);

            // Note: User model has 'password' => 'hashed' cast, so we don't need Hash::make()
            $user = User::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id, // For backward compatibility
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'], // Will be automatically hashed by the model cast
            ]);

            // Add user to organization via pivot table
            $user->organizations()->attach($organization->id, [
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);

            Auth::login($user);
            
            // Set current organization in session
            session(['current_organization_id' => $organization->id]);

            // Send welcome email
            try {
                $emailService = app(\App\Services\Admin\EmailService::class);
                $emailService->sendWelcomeEmail($user, $organization);
            } catch (\Exception $e) {
                // Log but don't fail registration if email fails
                \Log::warning('Failed to send welcome email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Redirect to onboarding conversation
            return redirect()->route('onboarding');
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Registration failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except(['password', 'password_confirmation']),
            ]);
            
            return back()->withErrors([
                'error' => 'Registration failed. Please try again.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }
    }

    private function createDefaultDashboardCards($organizationId)
    {
        $defaultCards = DashboardCard::where('is_default', true)->get();
        
        foreach ($defaultCards as $index => $card) {
            try {
                OrgDashboardCard::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organizationId,
                    'dashboard_card_id' => $card->id,
                    'display_order' => $index,
                    'is_visible' => true,
                    'width' => 8, // Default width
                    'height' => 8, // Default height
                    'row' => null, // Will be auto-positioned by frontend
                    'col' => null, // Will be auto-positioned by frontend
                ]);
            } catch (\Exception $e) {
                // Log but don't fail registration if card creation fails
                \Log::warning('Failed to create dashboard card for organization', [
                    'organization_id' => $organizationId,
                    'card_key' => $card->key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
