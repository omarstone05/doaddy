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
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Generate unique slug
            $baseSlug = Str::slug($validated['organization_name']);
            $slug = $baseSlug;
            $counter = 1;
            
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $organization = Organization::create([
                'id' => (string) Str::uuid(),
                'name' => $validated['organization_name'],
                'slug' => $slug,
                'tone_preference' => 'professional',
                'currency' => 'ZMW',
                'timezone' => 'Africa/Lusaka',
            ]);

            // Create default dashboard cards for the organization
            $this->createDefaultDashboardCards($organization->id);

            // Note: User model has 'password' => 'hashed' cast, so we don't need Hash::make()
            $user = User::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'], // Will be automatically hashed by the model cast
            ]);

            Auth::login($user);

            // Redirect to onboarding conversation
            return redirect()->route('onboarding');
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Registration failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except(['password', 'password_confirmation']),
            ]);

            // Return with error message
            return back()->withErrors([
                'email' => 'Registration failed. Please try again or contact support if the problem persists.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }
    }

    /**
     * Create default dashboard cards for a new organization
     */
    private function createDefaultDashboardCards(string $organizationId): void
    {
        // Default cards to create for new organizations
        $defaultCardKeys = [
            'total_revenue',
            'total_orders',
            'expenses_today',
            'net_balance',
            'revenue_chart',
            'cash_flow',
            'top_products',
            'top_customers',
            'recent_activity',
        ];

        // Get the dashboard card IDs
        $dashboardCards = DashboardCard::whereIn('key', $defaultCardKeys)
            ->where('is_active', true)
            ->get();

        // Create OrgDashboardCard instances for each default card
        foreach ($dashboardCards as $index => $card) {
            try {
                OrgDashboardCard::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organizationId,
                    'dashboard_card_id' => $card->id,
                    'config' => $card->default_config ?? [],
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
