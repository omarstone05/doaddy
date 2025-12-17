<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\UserMetricsService;
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

        // Check if user exists first
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            // Log failed login attempt - user not found
            SecurityEvent::logLoginFailure(
                $credentials['email'],
                'User not found',
                $request
            );
            
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('email', 'remember'));
        }

        // Check if user account is active
        if (!$user->is_active) {
            SecurityEvent::logLoginFailure(
                $credentials['email'],
                'Account deactivated',
                $request
            );
            
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ])->withInput($request->only('email', 'remember'));
        }

        // Attempt authentication
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Log successful login
            SecurityEvent::logLoginSuccess($user, $request, [
                'login_method' => 'email',
                'remember_me' => $request->boolean('remember'),
            ]);
            
            // Track login metric
            try {
                app(UserMetricsService::class)->trackLogin($user);
            } catch (\Exception $e) {
                Log::warning('Failed to track user login metric', ['error' => $e->getMessage()]);
            }
            
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

        // Log failed login attempt - wrong password
        SecurityEvent::logLoginFailure(
            $credentials['email'],
            'Invalid password',
            $request
        );

        // If we get here, password is wrong
        return back()->withErrors([
            'password' => 'The password you entered is incorrect. Please try again.',
        ])->withInput($request->only('email', 'remember'));
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        
        // Log logout event
        if ($user) {
            SecurityEvent::logLogout($user, $request);
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
