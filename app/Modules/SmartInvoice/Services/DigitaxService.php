<?php

namespace Addy\Modules\SmartInvoice\Services;

use Addy\Modules\SmartInvoice\Models\DigitaxCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Exception;

class DigitaxService
{
    protected DigitaxCredential $credential;
    protected string $apiUrl;
    protected array $defaultHeaders = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    public function __construct(DigitaxCredential $credential)
    {
        $this->credential = $credential;
        $this->apiUrl = $credential->getApiUrl();
    }

    /**
     * Test connection to Digitax API
     * 
     * @return array ['success' => bool, 'message' => string, 'data' => array|null, 'error' => string|null]
     */
    public function testConnection(): array
    {
        try {
            // Step 1: Test basic connectivity
            $healthCheck = $this->makeRequest('GET', '/api/v1/health');
            
            if (!$healthCheck->successful()) {
                return [
                    'success' => false,
                    'message' => 'API endpoint not accessible',
                    'data' => null,
                    'error' => "HTTP {$healthCheck->status()}: {$healthCheck->body()}",
                ];
            }

            // Step 2: Test authentication
            $authCheck = $this->makeRequest('GET', '/api/v1/auth/verify');
            
            if ($authCheck->status() === 401) {
                return [
                    'success' => false,
                    'message' => 'Authentication failed - Invalid credentials',
                    'data' => null,
                    'error' => 'API key or secret is invalid',
                ];
            }

            if (!$authCheck->successful()) {
                return [
                    'success' => false,
                    'message' => 'Authentication error',
                    'data' => null,
                    'error' => "HTTP {$authCheck->status()}: {$authCheck->body()}",
                ];
            }

            // Step 3: Test tax calculation capability
            $testPayload = [
                'income' => 50000,
                'jurisdiction' => 'US',
                'state' => 'CA',
                'deductions' => 5000,
                'filingStatus' => 'single',
            ];

            $calcCheck = $this->makeRequest('POST', '/api/v1/tax/calculate', $testPayload);

            if (!$calcCheck->successful()) {
                return [
                    'success' => false,
                    'message' => 'Tax calculation not working',
                    'data' => null,
                    'error' => "HTTP {$calcCheck->status()}: {$calcCheck->body()}",
                ];
            }

            // All tests passed
            return [
                'success' => true,
                'message' => 'Connection successful - All tests passed',
                'data' => [
                    'api_url' => $this->apiUrl,
                    'environment' => $this->credential->environment,
                    'health_status' => $healthCheck->json(),
                    'auth_verified' => true,
                    'calculation_available' => true,
                    'tested_at' => now()->toIso8601String(),
                ],
                'error' => null,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make an authenticated request to Digitax API
     */
    protected function makeRequest(string $method, string $path, array $payload = []): Response
    {
        $timestamp = (string) floor(microtime(true) * 1000);
        $signature = $this->generateSignature($method, $path, $timestamp, $payload);

        $headers = array_merge(
            $this->defaultHeaders,
            [
                'X-API-Key' => $this->credential->api_key,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ]
        );

        $url = $this->apiUrl . $path;

        if ($method === 'GET') {
            return Http::withHeaders($headers)->get($url);
        } else {
            return Http::withHeaders($headers)->post($url, $payload);
        }
    }

    /**
     * Generate HMAC-SHA256 signature for request authentication
     */
    protected function generateSignature(string $method, string $path, string $timestamp, array $payload = []): string
    {
        $payloadStr = !empty($payload) ? json_encode($payload) : '';
        $message = "{$method}\n{$path}\n{$timestamp}\n{$payloadStr}";

        return base64_encode(
            hash_hmac('sha256', $message, $this->credential->api_secret, true)
        );
    }

    /**
     * Calculate taxes using Digitax API
     * 
     * @param array $taxData Income, deductions, jurisdiction, etc.
     * @return array Calculation result with taxes and effective rate
     */
    public function calculateTax(array $taxData): array
    {
        $response = $this->makeRequest('POST', '/api/v1/tax/calculate', $taxData);

        if (!$response->successful()) {
            throw new Exception("Tax calculation failed: {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Generate compliance report
     */
    public function generateComplianceReport(array $reportData): array
    {
        $response = $this->makeRequest('POST', '/api/v1/compliance/report', $reportData);

        if (!$response->successful()) {
            throw new Exception("Compliance report generation failed: {$response->body()}");
        }

        return $response->json();
    }
}
