<?php

namespace App\Services\Budget;

use App\Models\Organization;
use App\Models\User;
use Firebase\JWT\JWT;

class BudgetTokenService
{
    public function issue(User $user): string
    {
        $organizationId = $user->current_organization_id;
        $organization = $organizationId ? Organization::find($organizationId) : null;

        if (!$organization) {
            throw new \RuntimeException('No organization found for user');
        }

        $secret = config('services.budgets.jwt_secret');
        if (empty($secret)) {
            throw new \RuntimeException('Budgets JWT secret not configured');
        }

        $now = now();
        $role = $user->getRoleInOrganization($organization->id) ?? 'member';

        $payload = [
            'iss' => 'addy',
            'sub' => $user->id,
            'iat' => $now->timestamp,
            'exp' => $now->copy()->addMinutes(30)->timestamp,
            'organization' => [
                'parent_app' => 'addy',
                'parent_id' => $organization->id,
                'name' => $organization->name,
                'type' => $organization->business_type ?? 'company',
                'currency_code' => $organization->currency ?? 'ZMW',
                'settings' => $organization->settings ?? [],
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
            ],
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
}
