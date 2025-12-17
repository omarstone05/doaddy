<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class LencoService
{
    protected string $baseUrl;
    protected string $secretKey;
    protected string $publicKey;
    protected string $apiName;

    public function __construct()
    {
        $this->baseUrl = config('services.lenco.base_url', 'https://api.lenco.co/access/v2');
        $this->secretKey = config('services.lenco.secret_key', env('LENCO_SECRET_KEY'));
        $this->publicKey = config('services.lenco.public_key', env('LENCO_PUBLIC_KEY'));
        $this->apiName = config('services.lenco.api_name', 'Addy');
    }

    /**
     * Initialize a payment
     */
    public function initializePayment(array $data): array
    {
        try {
            $payload = [
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'ZMW',
                'email' => $data['email'],
                'reference' => $data['reference'] ?? $this->generateReference(),
                'callback_url' => $data['callback_url'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ];

            // Add name if provided
            if (isset($data['name'])) {
                $payload['name'] = $data['name'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/payment/initialize", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'authorization_url' => $response->json('data.authorization_url'),
                    'reference' => $response->json('data.reference'),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message', 'Payment initialization failed'),
                'errors' => $response->json('errors', []),
            ];
        } catch (\Exception $e) {
            Log::error('Lenco payment initialization error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while initializing payment',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a payment
     */
    public function verifyPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/payment/verify/{$reference}");

            if ($response->successful()) {
                $data = $response->json('data');
                return [
                    'success' => true,
                    'data' => $data,
                    'status' => $data['status'] ?? 'pending',
                    'amount' => $data['amount'] ?? 0,
                    'currency' => $data['currency'] ?? 'ZMW',
                    'reference' => $data['reference'] ?? $reference,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message', 'Payment verification failed'),
            ];
        } catch (\Exception $e) {
            Log::error('Lenco payment verification error', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while verifying payment',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment details
     */
    public function getPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/payment/{$reference}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message', 'Failed to fetch payment details'),
            ];
        } catch (\Exception $e) {
            Log::error('Lenco get payment error', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while fetching payment',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List transactions
     */
    public function listTransactions(array $filters = []): array
    {
        try {
            $queryParams = http_build_query($filters);
            $url = "{$this->baseUrl}/payment/transactions";
            if ($queryParams) {
                $url .= '?' . $queryParams;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message', 'Failed to fetch transactions'),
            ];
        } catch (\Exception $e) {
            Log::error('Lenco list transactions error', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while fetching transactions',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $signature, array $payload): bool
    {
        // Implement webhook signature verification based on Lenco's documentation
        // This is a placeholder - adjust based on actual Lenco webhook implementation
        $expectedSignature = hash_hmac('sha256', json_encode($payload), $this->secretKey);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate a unique reference
     */
    protected function generateReference(): string
    {
        return $this->apiName . '_' . time() . '_' . uniqid();
    }

    /**
     * Get public key (for frontend use)
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}

