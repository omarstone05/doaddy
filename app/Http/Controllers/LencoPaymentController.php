<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Customer;
use App\Services\Payment\LencoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LencoPaymentController extends Controller
{
    protected LencoService $lencoService;

    public function __construct(LencoService $lencoService)
    {
        $this->lencoService = $lencoService;
    }

    /**
     * Initialize a payment with Lenco
     */
    public function initialize(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'callback_url' => 'nullable|url',
            'metadata' => 'nullable|array',
        ]);

        $customer = Customer::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($validated['customer_id']);

        $organization = Auth::user()->organization;

        $paymentData = [
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? $organization->currency ?? 'ZMW',
            'email' => $customer->email ?? Auth::user()->email,
            'name' => $customer->name,
            'callback_url' => $validated['callback_url'] ?? route('lenco.callback'),
            'metadata' => array_merge($validated['metadata'] ?? [], [
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'user_id' => Auth::id(),
            ]),
        ];

        $result = $this->lencoService->initializePayment($paymentData);

        if ($result['success']) {
            // Store pending payment record
            $payment = Payment::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'amount' => $validated['amount'],
                'currency' => $paymentData['currency'],
                'payment_date' => now(),
                'payment_method' => 'card', // Lenco typically handles card payments
                'payment_reference' => $result['reference'],
                'notes' => 'Payment via Lenco - Pending',
            ]);

            return response()->json([
                'success' => true,
                'authorization_url' => $result['authorization_url'],
                'reference' => $result['reference'],
                'payment_id' => $payment->id,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to initialize payment',
        ], 400);
    }

    /**
     * Verify a payment
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string',
        ]);

        $result = $this->lencoService->verifyPayment($validated['reference']);

        if ($result['success']) {
            // Update payment record
            $payment = Payment::where('payment_reference', $validated['reference'])->first();
            
            if ($payment) {
                $status = $result['status'];
                $payment->update([
                    'notes' => "Payment via Lenco - {$status}",
                ]);

                // If payment is successful, update related records
                if ($status === 'success' || $status === 'successful') {
                    // Payment is already created with money movement via Payment model boot
                }
            }

            return response()->json([
                'success' => true,
                'status' => $result['status'],
                'data' => $result['data'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Payment verification failed',
        ], 400);
    }

    /**
     * Handle Lenco webhook
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Lenco-Signature');

        Log::info('Lenco webhook received', [
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

        $payment = Payment::where('payment_reference', $reference)->first();

        if (!$payment) {
            Log::warning('Payment not found for Lenco webhook', ['reference' => $reference]);
            return response()->json(['error' => 'Payment not found'], 404);
        }

        DB::beginTransaction();
        try {
            $status = $payload['data']['status'] ?? $payload['status'] ?? 'pending';

            switch ($status) {
                case 'success':
                case 'successful':
                    $payment->update([
                        'notes' => 'Payment via Lenco - Completed',
                    ]);
                    break;

                case 'failed':
                case 'failure':
                    $payment->update([
                        'notes' => 'Payment via Lenco - Failed',
                    ]);
                    break;

                default:
                    $payment->update([
                        'notes' => "Payment via Lenco - {$status}",
                    ]);
            }

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing Lenco webhook', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Payment callback (redirect after payment)
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('payments.index')
                ->with('error', 'Payment reference not found');
        }

        // Verify payment
        $result = $this->lencoService->verifyPayment($reference);

        if ($result['success']) {
            $payment = Payment::where('payment_reference', $reference)->first();

            if ($payment) {
                $status = $result['status'];
                
                if ($status === 'success' || $status === 'successful') {
                    return redirect()->route('payments.show', $payment->id)
                        ->with('success', 'Payment completed successfully!');
                } else {
                    return redirect()->route('payments.show', $payment->id)
                        ->with('error', "Payment status: {$status}");
                }
            }
        }

        return redirect()->route('payments.index')
            ->with('error', 'Payment verification failed');
    }
}

