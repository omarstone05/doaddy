<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Services\Payment\LencoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    protected LencoService $lencoService;

    public function __construct(LencoService $lencoService)
    {
        $this->lencoService = $lencoService;
    }

    /**
     * Show subscription plans
     */
    public function index()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        $organization = Auth::user()->organization;
        $activeSubscription = $organization->activeSubscription();
        
        // Load plan relationship if subscription exists
        if ($activeSubscription) {
            $activeSubscription->load('plan');
        }

        return Inertia::render('Subscriptions/Index', [
            'plans' => $plans,
            'currentSubscription' => $activeSubscription,
            'organization' => $organization,
        ]);
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|uuid|exists:subscription_plans,id',
        ]);

        $organization = Auth::user()->organization;
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Check if organization already has an active subscription
        $activeSubscription = $organization->activeSubscription();
        if ($activeSubscription && $activeSubscription->subscription_plan_id === $plan->id) {
            return back()->withErrors(['error' => 'You are already subscribed to this plan']);
        }

        DB::beginTransaction();
        try {
            // Create subscription record
            $trialEndsAt = $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null;
            
            $subscription = Subscription::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'pending',
                'starts_at' => now(),
                'trial_ends_at' => $trialEndsAt,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'billing_period' => $plan->billing_period,
            ]);

            // Initialize payment with Lenco
            $paymentData = [
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'email' => Auth::user()->email,
                'name' => $organization->name,
                'callback_url' => route('subscriptions.callback'),
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'organization_id' => $organization->id,
                    'plan_id' => $plan->id,
                    'type' => 'subscription',
                ],
            ];

            $result = $this->lencoService->initializePayment($paymentData);

            if ($result['success']) {
                // Update subscription with Lenco reference
                $subscription->update([
                    'lenco_reference' => $result['reference'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'authorization_url' => $result['authorization_url'],
                    'subscription_id' => $subscription->id,
                ]);
            }

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initialize subscription payment',
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle subscription callback
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'Payment reference not found');
        }

        // Verify payment
        $result = $this->lencoService->verifyPayment($reference);

        if ($result['success']) {
            $subscription = Subscription::where('lenco_reference', $reference)->first();

            if ($subscription) {
                $status = $result['status'];
                
                DB::beginTransaction();
                try {
                    if ($status === 'success' || $status === 'successful') {
                        // Calculate end date based on billing period
                        $endsAt = match($subscription->billing_period) {
                            'monthly' => now()->addMonth(),
                            'yearly' => now()->addYear(),
                            default => now()->addMonth(),
                        };

                        $subscription->update([
                            'status' => 'active',
                            'ends_at' => $endsAt,
                        ]);

                        // Update organization
                        $subscription->organization->update([
                            'billing_plan' => $subscription->plan->slug,
                            'mrr' => $subscription->amount,
                            'status' => 'active',
                        ]);

                        // Send renewal email
                        try {
                            $listener = app(\App\Listeners\SendSubscriptionRenewalEmail::class);
                            $listener->handle($subscription);
                        } catch (\Exception $e) {
                            \Log::error('Failed to send subscription renewal email', [
                                'subscription_id' => $subscription->id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        DB::commit();

                        return redirect()->route('subscriptions.index')
                            ->with('success', 'Subscription activated successfully!');
                    } else {
                        $subscription->update([
                            'status' => 'failed',
                        ]);

                        DB::commit();

                        return redirect()->route('subscriptions.index')
                            ->with('error', "Payment failed. Status: {$status}");
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    return redirect()->route('subscriptions.index')
                        ->with('error', 'An error occurred while processing your subscription');
                }
            }
        }

        return redirect()->route('subscriptions.index')
            ->with('error', 'Subscription verification failed');
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $organization = Auth::user()->organization;
        $subscription = $organization->activeSubscription();

        if (!$subscription) {
            return back()->withErrors(['error' => 'No active subscription found']);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['reason'] ?? null,
            'ends_at' => $subscription->ends_at ?? now()->addMonth(), // Allow access until end of billing period
        ]);

        return back()->with('success', 'Subscription cancelled successfully. You will retain access until the end of your billing period.');
    }
}

