<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\NeuroCore\Adapters\AddyNeuroAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * NeuroController - API endpoint for testing NeuroCore
 * 
 * This is a SEPARATE endpoint from the main Addy chat.
 * Use it to test Neuro without affecting existing users.
 * 
 * Routes (add to routes/api.php):
 *   Route::prefix('neuro')->middleware('auth:sanctum')->group(function () {
 *       Route::post('/chat', [NeuroController::class, 'chat']);
 *       Route::get('/profile', [NeuroController::class, 'profile']);
 *       Route::get('/goals', [NeuroController::class, 'goals']);
 *       Route::post('/goals', [NeuroController::class, 'trackGoal']);
 *       Route::put('/goals/{goalId}/progress', [NeuroController::class, 'updateGoalProgress']);
 *       Route::get('/history', [NeuroController::class, 'history']);
 *       Route::post('/new-conversation', [NeuroController::class, 'newConversation']);
 *   });
 */
class NeuroController extends Controller
{
    /**
     * Get or create Neuro adapter for current user
     */
    private function getNeuro(Request $request): AddyNeuroAdapter
    {
        $user = $request->user();
        $organization = $user->organization;

        if (!$organization) {
            abort(400, 'User must belong to an organization');
        }

        return AddyNeuroAdapter::forUser($user, $organization);
    }

    /**
     * POST /api/neuro/chat
     * Send a message to Neuro
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'context' => 'array',
        ]);

        try {
            $neuro = $this->getNeuro($request);
            $response = $neuro->chat(
                $request->input('message'),
                $request->input('context', [])
            );

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/neuro/profile
     * Get user's Neuro profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $neuro = $this->getNeuro($request);
            $profile = $neuro->getProfile();

            return response()->json([
                'success' => true,
                'data' => $profile,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/neuro/goals
     * Get user's active goals
     */
    public function goals(Request $request): JsonResponse
    {
        try {
            $neuro = $this->getNeuro($request);
            $goals = $neuro->getActiveGoals();

            return response()->json([
                'success' => true,
                'data' => $goals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/neuro/goals
     * Track a new goal
     */
    public function trackGoal(Request $request): JsonResponse
    {
        $request->validate([
            'description' => 'required|string|max:500',
            'category' => 'string|max:50',
            'target_date' => 'date',
            'target_value' => 'numeric',
        ]);

        try {
            $neuro = $this->getNeuro($request);
            $goal = $neuro->trackGoal(
                $request->input('description'),
                $request->except('description')
            );

            return response()->json([
                'success' => true,
                'data' => $goal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/neuro/goals/{goalId}/progress
     * Update goal progress
     */
    public function updateGoalProgress(Request $request, string $goalId): JsonResponse
    {
        $request->validate([
            'progress' => 'required|numeric|min:0|max:1',
        ]);

        try {
            $neuro = $this->getNeuro($request);
            $goal = $neuro->updateGoalProgress(
                $goalId,
                $request->input('progress')
            );

            if (!$goal) {
                return response()->json([
                    'success' => false,
                    'error' => 'Goal not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $goal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/neuro/history
     * Get conversation history
     */
    public function history(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);

        try {
            $neuro = $this->getNeuro($request);
            $history = $neuro->getHistory($limit);

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/neuro/new-conversation
     * Start a new conversation
     */
    public function newConversation(Request $request): JsonResponse
    {
        try {
            $neuro = $this->getNeuro($request);
            $conversationId = $neuro->startNewConversation();

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversationId,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/neuro/insight
     * Get proactive insight if any
     */
    public function insight(Request $request): JsonResponse
    {
        try {
            $neuro = $this->getNeuro($request);
            $insight = $neuro->getProactiveInsight();

            return response()->json([
                'success' => true,
                'data' => $insight,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/neuro/export
     * Export user's Neuro data
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $neuro = $this->getNeuro($request);
            $data = $neuro->exportUserData();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}


