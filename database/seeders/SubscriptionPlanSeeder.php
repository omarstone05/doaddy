<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses getting started',
                'price' => 99.00,
                'currency' => 'ZMW',
                'billing_period' => 'monthly',
                'trial_days' => 14,
                'features' => [
                    'Up to 5 users',
                    'Basic reporting',
                    'Email support',
                    'Mobile app access',
                ],
                'max_users' => 5,
                'max_organizations' => 1,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing businesses that need more power',
                'price' => 299.00,
                'currency' => 'ZMW',
                'billing_period' => 'monthly',
                'trial_days' => 14,
                'features' => [
                    'Up to 25 users',
                    'Advanced reporting & analytics',
                    'Priority support',
                    'API access',
                    'Custom integrations',
                    'Team collaboration tools',
                ],
                'max_users' => 25,
                'max_organizations' => 1,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large organizations with advanced needs',
                'price' => 999.00,
                'currency' => 'ZMW',
                'billing_period' => 'monthly',
                'trial_days' => 14,
                'features' => [
                    'Unlimited users',
                    'Custom reporting & dashboards',
                    '24/7 dedicated support',
                    'Full API access',
                    'White-label options',
                    'Advanced security features',
                    'Custom integrations',
                    'Dedicated account manager',
                ],
                'max_users' => null,
                'max_organizations' => 1,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}

