<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppVerification;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        // Check if this is a CSRF token mismatch (419 error)
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your session has expired. Please refresh the page and try again.',
            ], 419);
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Set current organization in session (use first organization or existing session)
            $currentOrgId = session('current_organization_id') 
                ?? ($user->attributes['organization_id'] ?? null)
                ?? $user->organizations()->first()?->id;
            
            if ($currentOrgId) {
                session(['current_organization_id' => $currentOrgId]);
                // Update organization_id for backward compatibility
                $user->update(['organization_id' => $currentOrgId]);
            }
            
            // Redirect super admins to admin dashboard
            if ($user->isSuperAdmin()) {
                return redirect()->intended('/admin/dashboard');
            }
            
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    /**
     * Send WhatsApp verification code for login
     */
    public function sendWhatsAppCode(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        try {
            $phoneNumber = $request->phone_number;
            
            // Normalize phone number using WhatsAppService
            $whatsappService = new WhatsAppService();
            $normalizedPhone = $whatsappService->formatPhoneNumberForApi($phoneNumber);
            
            // Check if user exists with this phone number
            $user = User::where('phone_number', $normalizedPhone)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this phone number. Please register first.',
                ], 404);
            }

            // Create verification code
            $verification = WhatsAppVerification::createVerification($phoneNumber, $user->id);
            
            // Send via WhatsApp service
            $result = $whatsappService->sendVerificationCode($phoneNumber, $verification->code);
            
            if (!$result['success']) {
                Log::error('Failed to send WhatsApp verification code', [
                    'phone' => $phoneNumber,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Failed to send verification code. Please try again.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification code has been sent to your WhatsApp number.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp login code send exception', [
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
     * Verify WhatsApp code and login
     */
    public function verifyWhatsAppCode(Request $request)
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
            
            // Find user
            $user = User::where('phone_number', $normalizedPhone)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this phone number.',
                ], 404);
            }

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

            // Mark verification as used
            $verification->markAsVerified();

            // Log the user in
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // Set current organization in session
            $currentOrgId = session('current_organization_id') 
                ?? ($user->attributes['organization_id'] ?? null)
                ?? $user->organizations()->first()?->id;
            
            if ($currentOrgId) {
                session(['current_organization_id' => $currentOrgId]);
                // Update organization_id for backward compatibility
                $user->update(['organization_id' => $currentOrgId]);
            }

            // Determine redirect URL
            $redirectUrl = $user->isSuperAdmin() ? '/admin/dashboard' : '/dashboard';

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'redirect' => $redirectUrl,
            ]);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp login verification exception', [
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
}
