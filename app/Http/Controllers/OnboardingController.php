<?php

namespace App\Http\Controllers;

use App\Services\Onboarding\BusinessClassifierService;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    protected OnboardingService $onboardingService;
    protected BusinessClassifierService $classifierService;

    public function __construct(
        OnboardingService $onboardingService,
        BusinessClassifierService $classifierService
    ) {
        $this->onboardingService = $onboardingService;
        $this->classifierService = $classifierService;
    }

    /**
     * Show onboarding page
     */
    public function index(Request $request)
    {
        // Check if user already has organization and completed onboarding
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        if ($user->current_organization_id) {
            $org = $user->organization;
            if ($org && $org->onboarding_completed_at) {
                return redirect()->route('dashboard');
            }
        }

        // Check for existing session
        try {
            $session = $this->onboardingService->getSession($user->id);
        } catch (\Exception $e) {
            \Log::error('Onboarding session error: ' . $e->getMessage());
            $session = null;
        }

        return Inertia::render('Onboarding/AddyOnboarding', [
            'user' => $user,
            'session' => $session,
        ]);
    }

    /**
     * Legacy show method for backward compatibility
     */
    public function show()
    {
        return $this->index(request());
    }

    /**
     * Classify business description using AI
     */
    public function classify(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:500',
        ]);

        $category = $this->classifierService->classify($request->description);

        return response()->json([
            'category' => $category,
            'confidence' => 0.85, // You can add confidence scoring
        ]);
    }

    /**
     * Save onboarding progress
     */
    public function saveProgress(Request $request)
    {
        $request->validate([
            'phase' => 'required|string',
            'data' => 'required|array',
        ]);

        $user = $request->user();

        $session = $this->onboardingService->saveProgress(
            $user->id,
            $request->phase,
            $request->data
        );

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }

    /**
     * Complete onboarding and create organization
     */
    public function complete(Request $request)
    {
        // Make validation more lenient with defaults
        $validated = $request->validate([
            'business_description' => 'nullable|string|max:500',
            'confirmed_category' => 'nullable|string',
            'priorities' => 'nullable|array',
            'team_size' => 'nullable|string',
            'income_pattern' => 'nullable|string',
        ]);

        $user = $request->user();

        try {
            // Provide defaults if missing
            $data = array_merge([
                'business_description' => $validated['business_description'] ?? 'My Business',
                'confirmed_category' => $validated['confirmed_category'] ?? 'General Business',
                'priorities' => $validated['priorities'] ?? ['everything'],
                'team_size' => $validated['team_size'] ?? '2-5',
                'income_pattern' => $validated['income_pattern'] ?? 'monthly',
            ], $validated);

            $result = $this->onboardingService->completeOnboarding(
                $user,
                $data
            );

            // Redirect based on data upload preference
            if ($request->wants_data_upload === 'yes') {
                return response()->json([
                    'success' => true,
                    'organization' => $result['organization'],
                    'redirect' => route('data-upload.index'),
                    'message' => 'Welcome to Addy! Let\'s import your data.',
                ]);
            }

            return response()->json([
                'success' => true,
                'organization' => $result['organization'],
                'redirect' => route('dashboard'),
                'message' => 'Welcome to Addy! Let\'s get started.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Onboarding completion error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'There was an error completing onboarding. Please try again or skip for now.',
            ], 500);
        }
    }

    /**
     * Skip onboarding and create minimal organization
     */
    public function skip(Request $request)
    {
        $user = $request->user();

        try {
            // Create minimal organization
            $organization = $user->organization;
            
            if (!$organization) {
                $organization = \App\Models\Organization::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'name' => $user->name . "'s Business",
                    'slug' => \Illuminate\Support\Str::slug($user->name . '-business'),
                    'owner_id' => $user->id,
                    'business_category' => 'General Business',
                    'onboarding_completed_at' => now(),
                ]);
            } else {
                $organization->update([
                    'onboarding_completed_at' => now(),
                ]);
            }

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
                'organization_id' => $organization->id,
            ]);
            
            // Set in session
            session(['current_organization_id' => $organization->id]);

            // Mark any existing onboarding session as complete
            $session = $this->onboardingService->getSession($user->id);
            if ($session) {
                $session->update([
                    'completed' => true,
                    'completed_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'organization' => $organization,
                'redirect' => route('dashboard'),
                'message' => 'Welcome to Addy! You can set up your business details later.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Onboarding skip error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'There was an error. Please try again.',
            ], 500);
        }
    }

    /**
     * Get onboarding session
     */
    public function getSession(Request $request)
    {
        $user = $request->user();
        
        $session = $this->onboardingService->getSession($user->id);

        return response()->json([
            'session' => $session,
        ]);
    }
}
