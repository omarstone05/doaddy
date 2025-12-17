<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class SyncJwtUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $payload = JWTAuth::setToken($token)->getPayload();
        } catch (JWTException $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $iss = $payload->get('iss');
        $allowedIssuers = collect(explode(',', env('BUDGETS_ALLOWED_ISS', 'addy,projjo')))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->all();

        if (!empty($allowedIssuers) && $iss && !in_array($iss, $allowedIssuers, true)) {
            return response()->json(['message' => 'Unauthorized issuer'], 401);
        }

        $orgData = $payload->get('organization');
        $userData = $payload->get('user');
        $sub = $payload->get('sub');

        if (!$orgData || !$userData || !$sub) {
            return response()->json(['message' => 'Token missing required claims'], 401);
        }

        $organization = Organization::updateOrCreate(
            [
                'parent_app' => $orgData['parent_app'] ?? 'unknown',
                'parent_id' => $orgData['parent_id'] ?? $orgData['id'] ?? null,
            ],
            [
                'name' => $orgData['name'] ?? 'Unknown',
                'type' => $orgData['type'] ?? 'company',
                'currency_code' => $orgData['currency_code'] ?? 'ZMW',
                'settings' => $orgData['settings'] ?? [],
                'is_active' => true,
            ]
        );

        $user = User::updateOrCreate(
            [
                'parent_user_id' => $userData['id'] ?? $sub,
                'organization_id' => $organization->id,
            ],
            [
                'name' => $userData['name'] ?? 'Unknown',
                'email' => $userData['email'] ?? null,
                'role' => $userData['role'] ?? 'member',
            ]
        );

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
