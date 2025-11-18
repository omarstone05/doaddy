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
        
        if ($user->current_organization_id) {
            $org = $user->organization;
            if ($org && $org->onboarding_completed_at) {
                return redirect()->route('dashboard');
            }
        }

        // Check for existing session
        $session = $this->onboardingService->getSession($user->id);

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
        $request->validate([
            'business_description' => 'required|string',
            'confirmed_category' => 'required|string',
            'priorities' => 'array',
            'team_size' => 'required|string',
            'income_pattern' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $result = $this->onboardingService->completeOnboarding(
                $user,
                $request->all()
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
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
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
