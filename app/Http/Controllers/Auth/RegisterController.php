<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DashboardCard;
use App\Models\Organization;
use App\Models\OrgDashboardCard;
use App\Models\User;
use App\Models\WhatsAppVerification;
use App\Services\WhatsAppService;
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
            'password' => 'required|string|min:8|confirmed',
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

    /**
     * Send WhatsApp verification code for registration
     */
    public function sendRegistrationWhatsAppCode(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        try {
            $phoneNumber = $request->phone_number;
            
            // Normalize phone number using WhatsAppService
            $whatsappService = new WhatsAppService();
            $normalizedPhone = $whatsappService->formatPhoneNumberForApi($phoneNumber);
            
            // Check if user already exists with this phone number
            $existingUser = User::where('phone_number', $normalizedPhone)->first();
            
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'An account with this phone number already exists. Please login instead.',
                ], 409);
            }

            // Create verification code (no user_id since user doesn't exist yet)
            $verification = WhatsAppVerification::createVerification($phoneNumber, null);
            
            // Send via WhatsApp service
            $result = $whatsappService->sendVerificationCode($phoneNumber, $verification->code);
            
            if (!$result['success']) {
                Log::error('Failed to send WhatsApp verification code for registration', [
                    'phone' => $phoneNumber,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Failed to send verification code. Please try again.',
                ], 500);
            }

            $response = [
                'success' => true,
                'message' => $result['test_mode'] 
                    ? 'Local mode: Verification code sent. Check the response or logs for the code.'
                    : 'Verification code has been sent to your WhatsApp number.',
            ];

            // Include code in test mode
            if ($result['test_mode'] && isset($result['code'])) {
                $response['code'] = $result['code'];
                $response['test_mode'] = true;
            }

            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp registration code send exception', [
                'phone' => $request->phone_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the verification code. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify WhatsApp code for registration
     */
    public function verifyRegistrationWhatsAppCode(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        try {
            $phoneNumber = $request->phone_number;
            $code = $request->code;
            
            // Normalize phone number using WhatsAppService
            $whatsappService = new WhatsAppService();
            $normalizedPhone = $whatsappService->formatPhoneNumberForApi($phoneNumber);
            
            // Find valid verification code
            $verification = WhatsAppVerification::where('phone_number', $normalizedPhone)
                ->where('code', $code)
                ->where('verified', false)
                ->where('expires_at', '>', now())
                ->first();

            if (!$verification || !$verification->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification code. Please request a new code.',
                ], 400);
            }

            // Mark verification as verified (but don't delete it, we'll use it in storeWithWhatsApp)
            $verification->markAsVerified();

            return response()->json([
                'success' => true,
                'message' => 'Phone number verified successfully. Please complete your registration.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp registration verification exception', [
                'phone' => $request->phone_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during verification. Please try again.',
            ], 500);
        }
    }

    /**
     * Complete registration with verified WhatsApp phone number
     */
    public function storeWithWhatsApp(Request $request)
    {
        $validated = $request->validate([
            'organization_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $phoneNumber = $validated['phone_number'];
            $code = $validated['code'];
            
            // Normalize phone number using WhatsAppService
            $whatsappService = new WhatsAppService();
            $normalizedPhone = $whatsappService->formatPhoneNumberForApi($phoneNumber);
            
            // Verify the code was used
            $verification = WhatsAppVerification::where('phone_number', $normalizedPhone)
                ->where('code', $code)
                ->where('verified', true)
                ->where('expires_at', '>', now()->subMinutes(30)) // Allow 30 minutes for registration completion
                ->first();

            if (!$verification) {
                return back()->withErrors([
                    'phone_number' => 'Phone number verification expired or invalid. Please verify again.',
                ])->withInput($request->except(['password', 'password_confirmation']));
            }

            // Check if phone number is already registered
            $existingUser = User::where('phone_number', $normalizedPhone)->first();
            if ($existingUser) {
                return back()->withErrors([
                    'phone_number' => 'An account with this phone number already exists. Please login instead.',
                ])->withInput($request->except(['password', 'password_confirmation']));
            }

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

            // Create user with phone number and placeholder email
            $user = User::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id, // For backward compatibility
                'name' => $validated['name'],
                'email' => $normalizedPhone . '@whatsapp.addy', // Placeholder email
                'phone_number' => $normalizedPhone,
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
            Log::error('WhatsApp registration failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except(['password', 'password_confirmation']),
            ]);

            // Return with error message
            return back()->withErrors([
                'phone_number' => 'Registration failed. Please try again or contact support if the problem persists.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }
    }
}
