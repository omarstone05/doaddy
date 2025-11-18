<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Organization;
use App\Services\Addy\AddyCacheManager;
use App\Listeners\SendPaymentConfirmationEmail;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $this->handleChange($payment);
    }

    public function updated(Payment $payment): void
    {
        $this->handleChange($payment);
        
        // Send payment confirmation email if payment was just completed
        if ($payment->wasChanged('status') && 
            ($payment->status === 'completed' || $payment->status === 'successful')) {
            try {
                $listener = app(SendPaymentConfirmationEmail::class);
                $listener->handle($payment);
            } catch (\Exception $e) {
                Log::error('Failed to trigger payment confirmation email', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function deleted(Payment $payment): void
    {
        $this->handleChange($payment);
    }

    protected function handleChange(Payment $payment): void
    {
        // Clear SalesAgent cache (payments affect sales insights)
        AddyCacheManager::clearAgent('SalesAgent', $payment->organization_id);
        AddyCacheManager::clearOrganization($payment->organization_id);
        
        // Regenerate insights with fresh data
        try {
            $organization = Organization::find($payment->organization_id);
            if ($organization) {
                \App\Jobs\RegenerateAddyInsights::dispatch($organization)->delay(now()->addSeconds(5));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue insight regeneration', [
                'error' => $e->getMessage(),
                'organization_id' => $payment->organization_id,
            ]);
        }
    }
}

