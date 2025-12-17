<?php

namespace App\Listeners;

use App\Models\Payment;
use App\Services\Admin\EmailService;
use Illuminate\Support\Facades\Log;

class SendPaymentConfirmationEmail
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle payment created/updated events
     */
    public function handle($event): void
    {
        $payment = $event->payment ?? $event;
        
        if (!$payment instanceof Payment) {
            return;
        }

        // Only send for successful payments
        if ($payment->status !== 'completed' && $payment->status !== 'successful') {
            return;
        }

        try {
            $organization = $payment->organization;
            $user = $organization->users()->first();

            if (!$user || !$user->email) {
                return;
            }

            // Try to send using payment_confirmation template, fallback to custom email
            try {
                $this->emailService->sendFromTemplate(
                    'payment_confirmation',
                    $user->email,
                    [
                        'user_name' => $user->name,
                        'organization_name' => $organization->name,
                        'payment_amount' => number_format($payment->amount, 2),
                        'payment_reference' => $payment->payment_reference ?? $payment->id,
                        'payment_date' => $payment->created_at->format('F j, Y'),
                        'payment_method' => $payment->payment_method ?? 'N/A',
                    ],
                    $organization,
                    $user
                );
            } catch (\Exception $e) {
                // Template doesn't exist, send custom email
                $this->emailService->send(
                    to: $user->email,
                    subject: "Payment Confirmation - {$payment->payment_reference}",
                    body: "Hi {$user->name},\n\nYour payment of " . number_format($payment->amount, 2) . " has been confirmed.\n\nReference: {$payment->payment_reference}\nDate: " . $payment->created_at->format('F j, Y') . "\n\nThank you for your payment!\n\nBest regards,\nAddy Business Team",
                    organization: $organization,
                    user: $user
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
