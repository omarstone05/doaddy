<?php

namespace Addy\Modules\SmartInvoice\Services;

use Addy\Modules\SmartInvoice\Models\DigitaxCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Exception;

/**
 * DigiTax Zambia API client (zm.docs.digitax.tech).
 *
 * - Auth: X-API-Key header only (Integrations key).
 * - Invalid key → 403 Forbidden with {"message": "Unauthorized"}.
 * - Endpoints: Items, Sales, Stock, Import; queue-based transaction status.
 *
 * @see https://zm.docs.digitax.tech/docs/start-using-the-api
 * @see https://zm.docs.digitax.tech/docs/errors
 * @see apps/addy/docs/DIGITAX_ZAMBIA_API_REFERENCE.md
 */
class DigitaxService
{
    protected DigitaxCredential $credential;

    protected string $apiUrl;

    /** @var string Path prefix for Zambia API, e.g. /api/v1 */
    protected string $pathPrefix;

    protected array $defaultHeaders = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    public function __construct(DigitaxCredential $credential)
    {
        $this->credential = $credential;
        $this->apiUrl = rtrim($credential->getApiUrl(), '/');
        $this->pathPrefix = rtrim(
            (string) (config('services.digitax.api_path_prefix') ?? '/api/v1'),
            '/'
        );
    }

    /**
     * Test connection using a real Zambia endpoint.
     * Invalid X-API-Key → 403 with {"message": "Unauthorized"} (per Errors doc).
     */
    public function testConnection(): array
    {
        try {
            $path = $this->pathPrefix . '/items';
            $response = $this->makeRequest('GET', $path, [], ['limit' => 1]);

            if ($response->status() === 403) {
                $body = $response->json();
                $msg = $body['message'] ?? 'Unauthorized';
                return [
                    'success' => false,
                    'message' => 'Invalid X-API-Key',
                    'data' => null,
                    'error' => $msg,
                ];
            }

            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'message' => 'Authentication failed',
                    'data' => null,
                    'error' => 'API key not accepted',
                ];
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => [
                        'api_url' => $this->apiUrl,
                        'path_prefix' => $this->pathPrefix,
                        'environment' => $this->credential->environment,
                        'tested_at' => now()->toIso8601String(),
                    ],
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'message' => 'API returned an error',
                'data' => null,
                'error' => "HTTP {$response->status()}: {$response->body()}",
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
     * Make an authenticated request.
     * Uses only X-API-Key when digitax_api_key is set (Zambia style).
     *
     * @param string $method GET|POST|PUT|DELETE
     * @param string $path   e.g. /api/v1/items or /api/v1/items/{id}
     * @param array  $body   JSON body for POST/PUT
     * @param array  $query  Query params for GET
     */
    public function makeRequest(string $method, string $path, array $body = [], array $query = []): Response
    {
        $apiKey = $this->getAuthApiKey();
        $headers = array_merge($this->defaultHeaders, [
            'X-API-Key' => $apiKey,
        ]);

        if (!$this->usesZambiaAuth() && !empty($this->credential->api_secret)) {
            $timestamp = (string) floor(microtime(true) * 1000);
            $signature = $this->generateSignature($method, $path, $timestamp, $body);
            $headers['X-Timestamp'] = $timestamp;
            $headers['X-Signature'] = $signature;
        }

        $url = $this->apiUrl . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $client = Http::withHeaders($headers);

        return match (strtoupper($method)) {
            'GET' => $client->get($url),
            'POST' => $client->post($url, $body),
            'PUT' => $client->put($url, $body),
            'DELETE' => $client->delete($url),
            default => $client->post($url, $body),
        };
    }

    protected function getAuthApiKey(): string
    {
        if (!empty($this->credential->digitax_api_key)) {
            return $this->credential->digitax_api_key;
        }
        return (string) ($this->credential->api_key ?? '');
    }

    protected function usesZambiaAuth(): bool
    {
        return !empty($this->credential->digitax_api_key);
    }

    protected function generateSignature(string $method, string $path, string $timestamp, array $payload = []): string
    {
        $payloadStr = !empty($payload) ? json_encode($payload) : '';
        $message = "{$method}\n{$path}\n{$timestamp}\n{$payloadStr}";
        return base64_encode(
            hash_hmac('sha256', $message, (string) ($this->credential->api_secret ?? ''), true)
        );
    }

    // ─── Items (zm.docs.digitax.tech/docs/items-general-data-attributes) ───

    /** GET /items */
    public function listItems(array $query = []): Response
    {
        return $this->makeRequest('GET', $this->pathPrefix . '/items', [], $query);
    }

    /** GET /items/{item_id} */
    public function getItem(string $itemId): Response
    {
        return $this->makeRequest('GET', $this->pathPrefix . '/items/' . $itemId);
    }

    /** POST /items */
    public function createItem(array $data): Response
    {
        return $this->makeRequest('POST', $this->pathPrefix . '/items', $data);
    }

    /** PUT /items/{item_id} */
    public function updateItem(string $itemId, array $data): Response
    {
        return $this->makeRequest('PUT', $this->pathPrefix . '/items/' . $itemId, $data);
    }

    /** DELETE /items/{item_id} */
    public function deleteItem(string $itemId): Response
    {
        return $this->makeRequest('DELETE', $this->pathPrefix . '/items/' . $itemId);
    }

    // ─── Sales (zm.docs.digitax.tech/docs/sales-data-attributes) ───

    /** POST sales (Sale / Credit Note / Debit Note). receipt_type_code: S|R|D */
    public function createSale(array $data): Response
    {
        return $this->makeRequest('POST', $this->pathPrefix . '/sales', $data);
    }

    /** GET /sales/{sale_id} */
    public function getSale(string $saleId): Response
    {
        return $this->makeRequest('GET', $this->pathPrefix . '/sales/' . $saleId);
    }

    // ─── Legacy helpers (if you still need them for non-Zambia flows) ───

    /** @deprecated Use createSale + ZRA attribute set for Zambia. Kept for backward compatibility. */
    public function calculateTax(array $taxData): array
    {
        $response = $this->makeRequest('POST', $this->pathPrefix . '/tax/calculate', $taxData);
        if (!$response->successful()) {
            throw new Exception("Tax calculation failed: {$response->body()}");
        }
        return $response->json();
    }

    /** @deprecated Use Items/Sales/Stock per Zambia docs. Kept for backward compatibility. */
    public function generateComplianceReport(array $reportData): array
    {
        $response = $this->makeRequest('POST', $this->pathPrefix . '/compliance/report', $reportData);
        if (!$response->successful()) {
            throw new Exception("Compliance report generation failed: {$response->body()}");
        }
        return $response->json();
    }
}
