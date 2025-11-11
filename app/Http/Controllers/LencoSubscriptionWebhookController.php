<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\Payment\LencoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LencoSubscriptionWebhookController extends Controller
{
    protected LencoService $lencoService;

    public function __construct(LencoService $lencoService)
    {
        $this->lencoService = $lencoService;
    }

    /**
     * Handle Lenco webhook for subscription payments
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Lenco-Signature');

        Log::info('Lenco subscription webhook received', [
            'payload' => $payload,
            'signature' => $signature,
        ]);

        // Verify webhook signature if provided
        if ($signature) {
            $isValid = $this->lencoService->verifyWebhookSignature($signature, $payload);
            if (!$isValid) {
                Log::warning('Invalid Lenco webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // Handle different webhook events
        $event = $payload['event'] ?? $payload['type'] ?? null;
        $reference = $payload['data']['reference'] ?? $payload['reference'] ?? null;

        if (!$reference) {
            return response()->json(['error' => 'Reference not found'], 400);
        }

        $subscription = Subscription::where('lenco_reference', $reference)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for Lenco webhook', ['reference' => $reference]);
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        DB::beginTransaction();
        try {
            $status = $payload['data']['status'] ?? $payload['status'] ?? 'pending';

            switch ($status) {
                case 'success':
                case 'successful':
                    // Renew subscription or activate
                    if ($subscription->status === 'active') {
                        // Renewal payment
                        $endsAt = match($subscription->billing_period) {
                            'monthly' => now()->addMonth(),
                            'yearly' => now()->addYear(),
                            default => now()->addMonth(),
                        };
                        $subscription->update([
                            'ends_at' => $endsAt,
                            'status' => 'active',
                        ]);
                    } else {
                        // Initial activation
                        $endsAt = match($subscription->billing_period) {
                            'monthly' => now()->addMonth(),
                            'yearly' => now()->addYear(),
                            default => now()->addMonth(),
                        };
                        $subscription->update([
                            'status' => 'active',
                            'ends_at' => $endsAt,
                        ]);
                        $subscription->organization->update([
                            'billing_plan' => $subscription->plan->slug,
                            'mrr' => $subscription->amount,
                            'status' => 'active',
                        ]);
                    }
                    break;

                case 'failed':
                case 'failure':
                    $subscription->update([
                        'status' => 'past_due',
                    ]);
                    break;

                default:
                    Log::info('Unhandled subscription webhook status', [
                        'status' => $status,
                        'reference' => $reference,
                    ]);
            }

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing Lenco subscription webhook', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
}

