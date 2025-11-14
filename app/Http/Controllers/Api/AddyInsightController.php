<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AddyInsight;
use App\Services\Addy\AddyCoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AddyInsightController extends Controller
{
    public function dismiss(Request $request, $insight)
    {
        try {
            // Find the insight - handle both route model binding and manual lookup
            if ($insight instanceof AddyInsight) {
                $insightModel = $insight;
            } else {
                $insightModel = AddyInsight::findOrFail($insight);
            }

            // Check if user is authenticated
            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            // Check organization ownership
            if ($insightModel->organization_id !== $request->user()->organization_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $insightModel->dismiss();

            return response()->json([
                'success' => true,
                'message' => 'Insight dismissed',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Insight not found for dismiss', [
                'insight_id' => $insight instanceof AddyInsight ? $insight->id : $insight,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insight not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to dismiss insight', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'insight_id' => $insight instanceof AddyInsight ? $insight->id : $insight,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to dismiss insight: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function complete(Request $request, $insight)
    {
        try {
            // Find the insight - handle both route model binding and manual lookup
            if ($insight instanceof AddyInsight) {
                $insightModel = $insight;
            } else {
                $insightModel = AddyInsight::findOrFail($insight);
            }

            // Check if user is authenticated
            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            // Check organization ownership
            if ($insightModel->organization_id !== $request->user()->organization_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $insightModel->complete();

            return response()->json([
                'success' => true,
                'message' => 'Insight completed',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Insight not found for complete', [
                'insight_id' => $insight instanceof AddyInsight ? $insight->id : $insight,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insight not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to complete insight', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'insight_id' => $insight instanceof AddyInsight ? $insight->id : $insight,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete insight: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $insights = AddyInsight::active($request->user()->organization_id)
            ->get()
            ->map(fn($insight) => [
                'id' => $insight->id,
                'type' => $insight->type,
                'category' => $insight->category,
                'title' => $insight->title,
                'description' => $insight->description,
                'priority' => (float) $insight->priority,
                'is_actionable' => $insight->is_actionable,
                'actions' => $insight->suggested_actions,
                'url' => $insight->action_url,
                'created_at' => $insight->created_at->diffForHumans(),
            ]);

        return response()->json($insights);
    }

    public function refresh(Request $request)
    {
        try {
            $organization = $request->user()->organization;
            $coreService = new AddyCoreService($organization);
            
            // Regenerate insights with latest data
            $coreService->regenerateInsights();
            
            // Get updated thought/insights
            $thought = $coreService->getCurrentThought();
            
            return response()->json([
                'success' => true,
                'message' => 'Insights refreshed successfully',
                'data' => $thought,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh insights', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh insights: ' . $e->getMessage(),
            ], 500);
        }
    }
}

