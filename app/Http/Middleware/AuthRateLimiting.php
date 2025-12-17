<?php

namespace App\Http\Middleware;

use App\Models\SecurityEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthRateLimiting
{
    /**
     * Handle an incoming request.
     * 
     * Implements rate limiting for authentication endpoints based on both
     * IP address and email (for login attempts).
     */
    public function handle(Request $request, Closure $next, string $type = 'login'): Response
    {
        $ip = $request->ip();
        $email = $request->input('email');
        
        // Check IP-based rate limiting using both in-memory and database
        $ipKey = "auth:{$type}:ip:{$ip}";
        $maxIpAttempts = $this->getMaxAttemptsForType($type, 'ip');
        $decayMinutes = $this->getDecayMinutes($type);
        
        // Check if IP is rate limited (in-memory via Redis/cache)
        if (RateLimiter::tooManyAttempts($ipKey, $maxIpAttempts)) {
            $seconds = RateLimiter::availableIn($ipKey);
            
            SecurityEvent::log(
                SecurityEvent::TYPE_SUSPICIOUS_ACTIVITY,
                'warning',
                null,
                $request,
                [
                    'reason' => 'rate_limit_exceeded',
                    'type' => $type,
                    'key' => 'ip',
                    'email' => $email,
                ]
            );
            
            return $this->buildRateLimitResponse($seconds);
        }
        
        // Check email-based rate limiting (if email provided)
        if ($email && $type === 'login') {
            $emailKey = "auth:login:email:" . strtolower($email);
            $maxEmailAttempts = $this->getMaxAttemptsForType('login', 'email');
            
            if (RateLimiter::tooManyAttempts($emailKey, $maxEmailAttempts)) {
                $seconds = RateLimiter::availableIn($emailKey);
                
                SecurityEvent::log(
                    SecurityEvent::TYPE_SUSPICIOUS_ACTIVITY,
                    'warning',
                    null,
                    $request,
                    [
                        'reason' => 'email_rate_limit_exceeded',
                        'email' => $email,
                    ]
                );
                
                return $this->buildRateLimitResponse($seconds, 'email');
            }
        }
        
        // Also check database for persistent rate limiting (survives cache clears)
        if (SecurityEvent::shouldRateLimitIp($ip)) {
            return $this->buildRateLimitResponse(900, 'ip', true); // 15 min lockout
        }
        
        if ($email && SecurityEvent::shouldRateLimitEmail($email)) {
            return $this->buildRateLimitResponse(900, 'email', true);
        }
        
        $response = $next($request);
        
        // If authentication failed (redirect back or 401/422), increment rate limiter
        if ($this->isFailedAuthResponse($response, $request)) {
            RateLimiter::hit($ipKey, $decayMinutes * 60);
            
            if ($email) {
                $emailKey = "auth:login:email:" . strtolower($email);
                RateLimiter::hit($emailKey, $decayMinutes * 60);
            }
        } else {
            // On success, clear rate limiters for this IP/email
            RateLimiter::clear($ipKey);
            if ($email) {
                $emailKey = "auth:login:email:" . strtolower($email);
                RateLimiter::clear($emailKey);
            }
        }
        
        return $response;
    }

    /**
     * Get maximum attempts for a given type and key
     */
    protected function getMaxAttemptsForType(string $type, string $key = 'ip'): int
    {
        $limits = [
            'login' => ['ip' => 10, 'email' => 5],
            'register' => ['ip' => 3, 'email' => 1],
            'password_reset' => ['ip' => 3, 'email' => 3],
            'whatsapp_code' => ['ip' => 5, 'email' => 3],
            'mfa' => ['ip' => 5, 'email' => 5],
        ];
        
        return $limits[$type][$key] ?? 10;
    }

    /**
     * Get decay time in minutes for a given type
     */
    protected function getDecayMinutes(string $type): int
    {
        $decayMinutes = [
            'login' => 15,
            'register' => 60,
            'password_reset' => 60,
            'whatsapp_code' => 5,
            'mfa' => 5,
        ];
        
        return $decayMinutes[$type] ?? 15;
    }

    /**
     * Check if the response indicates a failed authentication
     */
    protected function isFailedAuthResponse(Response $response, Request $request): bool
    {
        $statusCode = $response->getStatusCode();
        
        // Check for validation errors or unauthorized responses
        if (in_array($statusCode, [401, 422, 429])) {
            return true;
        }
        
        // Check for redirect back (failed validation in web)
        if ($statusCode === 302 && $response->headers->get('Location') === $request->header('Referer')) {
            return true;
        }
        
        // Check for Inertia redirect back with errors
        if ($request->header('X-Inertia') && $statusCode === 302) {
            return true;
        }
        
        return false;
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildRateLimitResponse(int $seconds, string $limitedBy = 'ip', bool $persistent = false): Response
    {
        $minutes = ceil($seconds / 60);
        
        $message = match ($limitedBy) {
            'email' => "Too many login attempts for this account. Please try again in {$minutes} minute(s).",
            default => "Too many attempts from your location. Please try again in {$minutes} minute(s).",
        };
        
        if ($persistent) {
            $message = "Your account has been temporarily locked due to multiple failed attempts. Please try again in {$minutes} minute(s) or contact support.";
        }
        
        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'rate_limit_exceeded',
                'retry_after' => $seconds,
            ], 429);
        }
        
        return redirect()->back()
            ->withInput(request()->only('email', 'phone_number'))
            ->withErrors(['email' => $message]);
    }
}

