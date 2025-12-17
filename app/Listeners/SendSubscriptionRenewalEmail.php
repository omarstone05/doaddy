<?php

namespace App\Listeners;

use App\Models\Subscription;
use App\Services\Admin\EmailService;
use Illuminate\Support\Facades\Log;

class SendSubscriptionRenewalEmail
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle subscription renewal events
     */
    public function handle($event): void
    {
        $subscription = $event->subscription ?? $event;
        
        if (!$subscription instanceof Subscription) {
            return;
        }

        try {
            $organization = $subscription->organization;
            $user = $organization->users()->first();

            if (!$user || !$user->email) {
                return;
            }

            // Send renewal confirmation
            try {
                $this->emailService->sendFromTemplate(
                    'subscription_renewal',
                    $user->email,
                    [
                        'user_name' => $user->name,
                        'organization_name' => $organization->name,
                        'plan_name' => $subscription->plan->name ?? 'Premium',
                        'amount' => number_format($subscription->amount, 2),
                        'billing_period' => ucfirst($subscription->billing_period),
                        'renewal_date' => $subscription->ends_at->format('F j, Y'),
                    ],
                    $organization,
                    $user
                );
            } catch (\Exception $e) {
                // Template doesn't exist, send custom email
                $this->emailService->send(
                    to: $user->email,
                    subject: "Subscription Renewed - {$organization->name}",
                    body: "Hi {$user->name},\n\nYour subscription has been renewed successfully.\n\nPlan: " . ($subscription->plan->name ?? 'Premium') . "\nAmount: " . number_format($subscription->amount, 2) . "\nBilling Period: " . ucfirst($subscription->billing_period) . "\nNext Renewal: " . $subscription->ends_at->format('F j, Y') . "\n\nThank you for your continued support!\n\nBest regards,\nAddy Business Team",
                    organization: $organization,
                    user: $user
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send subscription renewal email', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
