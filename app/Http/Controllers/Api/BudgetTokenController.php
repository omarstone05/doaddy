<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Budget\BudgetTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetTokenController extends Controller
{
    public function __construct(private BudgetTokenService $tokenService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $moduleManager = app(\App\Support\ModuleManager::class);

        if (!$moduleManager->isEnabled('Budgets')) {
            return response()->json(['message' => 'Budgets module is disabled'], 403);
        }

        try {
            $token = $this->tokenService->issue($user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'expires_in' => 30 * 60,
        ]);
    }
}
