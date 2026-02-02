<?php

namespace App\Http\Controllers;

use App\Modules\SmartInvoice\Models\DigitaxCredential;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigitaxSettingsController extends Controller
{
    /**
     * Store or update DigiTax credentials
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => 'required|string|min:5',
            'environment' => 'required|in:sandbox,production',
        ]);

        $user = $request->user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organization_id;

        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'error' => 'No organization found',
            ], 400);
        }

        try {
            // Find or create credential for this organization
            // Note: api_key (Serial Number) and api_secret (TPIN) are populated 
            // automatically after successful connection test from DigiTax /info endpoint
            $credential = DigitaxCredential::updateOrCreate(
                ['organization_id' => $organizationId],
                [
                    'digitax_api_key' => $validated['api_key'],
                    'environment' => $validated['environment'],
                    'is_active' => false, // Will be activated after successful test
                    'api_key' => 'pending', // Will be populated from DigiTax /info
                    'api_secret' => 'pending', // Will be populated from DigiTax /info
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'DigiTax credentials saved successfully',
                'credential_id' => $credential->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save DigiTax credentials', [
                'error' => $e->getMessage(),
                'organization_id' => $organizationId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save credentials: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test DigiTax connection
     */
    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => 'required|string|min:5',
            'environment' => 'required|in:sandbox,production',
        ]);

        $user = $request->user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organization_id;

        // DigiTax uses a single API URL - sandbox/production is determined by 
        // business type (TEST/LIVE) in DigiTax dashboard, not by different URLs
        $baseUrl = 'https://api.digitax.tech/zm/v1';

        try {
            // Test the connection by calling the info endpoint
            $response = Http::withHeaders([
                'X-API-Key' => $validated['api_key'],
                'Accept' => 'application/json',
            ])->timeout(15)->get($baseUrl . '/info');

            if ($response->successful()) {
                $data = $response->json();
                
                // Update credential as active and populate business info from DigiTax
                if ($organizationId) {
                    $updateData = [
                        'is_active' => true,
                        'last_tested_at' => now(),
                        'test_error' => null,
                        'test_result' => $data,
                    ];
                    
                    // Extract TPIN from DigiTax response (field is called "tax_pin")
                    $tpin = $data['tax_pin'] ?? $data['tpin'] ?? null;
                    if (!empty($tpin)) {
                        $updateData['api_secret'] = $tpin;
                    }
                    
                    // Store branch ID if available
                    if (!empty($data['id'])) {
                        $updateData['api_key'] = $data['id'];
                    }
                    
                    DigitaxCredential::where('organization_id', $organizationId)
                        ->update($updateData);
                }

                // Extract TPIN for response
                $tpin = $data['tax_pin'] ?? $data['tpin'] ?? null;
                $branchId = $data['id'] ?? null;
                $isTest = $data['is_test'] ?? false;
                $isLive = $data['is_live'] ?? false;
                
                return response()->json([
                    'success' => true,
                    'message' => "Connection successful! TPIN: {$tpin}",
                    'data' => [
                        'tpin' => $tpin,
                        'branch_id' => $branchId,
                        'branch_office_id' => $data['branch_office_id'] ?? null,
                        'is_head_office' => $data['is_head_office'] ?? false,
                        'is_test' => $isTest,
                        'is_live' => $isLive,
                        'environment_status' => $isTest ? 'Sandbox (Test)' : ($isLive ? 'Production (Live)' : 'Unknown'),
                    ],
                ]);
            }

            // Handle specific error responses
            $error = 'Connection failed';
            if ($response->status() === 401) {
                $error = 'Invalid API key. Please check your DigiTax credentials.';
            } elseif ($response->status() === 403) {
                $error = 'API key is valid but lacks required permissions.';
            } elseif ($response->status() === 404) {
                $error = 'DigiTax API endpoint not found. Please check your environment setting.';
            }

            // Update credential with error
            if ($organizationId) {
                DigitaxCredential::where('organization_id', $organizationId)
                    ->update([
                        'is_active' => false,
                        'last_tested_at' => now(),
                        'test_error' => $error,
                    ]);
            }

            return response()->json([
                'success' => false,
                'error' => $error,
            ], 422);

        } catch (\Exception $e) {
            Log::error('DigiTax connection test failed', [
                'error' => $e->getMessage(),
                'environment' => $validated['environment'],
            ]);

            // Update credential with error
            if ($organizationId) {
                DigitaxCredential::where('organization_id', $organizationId)
                    ->update([
                        'is_active' => false,
                        'last_tested_at' => now(),
                        'test_error' => $e->getMessage(),
                    ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
