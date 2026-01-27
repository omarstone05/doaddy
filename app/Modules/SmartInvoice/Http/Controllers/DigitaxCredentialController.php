<?php

namespace Addy\Modules\SmartInvoice\Http\Controllers;

use Addy\Modules\SmartInvoice\Models\DigitaxCredential;
use Addy\Modules\SmartInvoice\Services\DigitaxService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class DigitaxCredentialController extends Controller
{
    /**
     * Get Digitax credentials for current organization
     */
    public function index(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id ?? auth()->user()->organization_id;

        $credentials = DigitaxCredential::where('organization_id', $organizationId)
            ->get()
            ->makeHidden(['api_secret']);

        return response()->json([
            'success' => true,
            'data' => $credentials,
        ]);
    }

    /**
     * Store new Digitax credentials
     */
    public function store(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id ?? auth()->user()->organization_id;

        // Validate input
        $validated = $request->validate([
            'api_key' => 'required|string|min:10',              // Serial Number
            'api_secret' => 'required|string|min:10',           // TPIN
            'digitax_api_key' => 'required|string|min:10',      // Digitax API Key
            'environment' => 'required|in:sandbox,production',
        ]);

        $validated['organization_id'] = $organizationId;
        $validated['is_active'] = false; // Inactive until tested

        $credential = DigitaxCredential::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Credentials stored. Please test the connection.',
            'data' => $credential->makeHidden(['api_secret', 'digitax_api_key']),
        ], 201);
    }

    /**
     * Update Digitax credentials
     */
    public function update(Request $request, DigitaxCredential $credential): JsonResponse
    {
        // Authorize user
        $this->authorize('update', $credential);

        $validated = $request->validate([
            'api_key' => 'sometimes|required|string|min:10',
            'api_secret' => 'sometimes|required|string|min:10',
            'digitax_api_key' => 'sometimes|required|string|min:10',
            'environment' => 'sometimes|required|in:sandbox,production',
            'is_active' => 'sometimes|boolean',
        ]);

        // If credentials changed, reset test status
        if (isset($validated['api_key']) || isset($validated['api_secret']) || isset($validated['digitax_api_key'])) {
            $validated['is_active'] = false;
            $validated['test_result'] = null;
            $validated['test_error'] = null;
        }

        $credential->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Credentials updated',
            'data' => $credential->makeHidden(['api_secret']),
        ]);
    }

    /**
     * Test Digitax connection
     */
    public function testConnection(Request $request, DigitaxCredential $credential): JsonResponse
    {
        // Authorize user
        $this->authorize('update', $credential);

        try {
            $service = new DigitaxService($credential);
            $testResult = $service->testConnection();

            // Update credential with test result
            $credential->update([
                'last_tested_at' => now(),
                'test_result' => $testResult['data'],
                'test_error' => $testResult['error'],
                'is_active' => $testResult['success'],
            ]);

            return response()->json([
                'success' => $testResult['success'],
                'message' => $testResult['message'],
                'data' => [
                    'credential_id' => $credential->id,
                    'is_active' => $credential->is_active,
                    'test_details' => $testResult['data'],
                    'tested_at' => $credential->last_tested_at->toIso8601String(),
                ],
                'error' => $testResult['error'],
            ], $testResult['success'] ? 200 : 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test connection failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete Digitax credentials
     */
    public function destroy(Request $request, DigitaxCredential $credential): JsonResponse
    {
        // Authorize user
        $this->authorize('delete', $credential);

        $credential->delete();

        return response()->json([
            'success' => true,
            'message' => 'Credentials deleted',
        ]);
    }

    /**
     * Get credential details (without exposing secret)
     */
    public function show(DigitaxCredential $credential): JsonResponse
    {
        // Authorize user
        $this->authorize('view', $credential);

        return response()->json([
            'success' => true,
            'data' => $credential->makeHidden(['api_secret']),
        ]);
    }
}
