<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AddyInsight;
use App\Services\Addy\AddyCoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AddyInsightController extends Controller
{
    public function dismiss(Request $request, AddyInsight $insight)
    {
        if ($insight->organization_id !== $request->user()->organization_id) {
            abort(403, 'Unauthorized');
        }

        $insight->dismiss();

        return response()->json([
            'success' => true,
            'message' => 'Insight dismissed',
        ]);
    }

    public function complete(Request $request, AddyInsight $insight)
    {
        if ($insight->organization_id !== $request->user()->organization_id) {
            abort(403, 'Unauthorized');
        }

        $insight->complete();

        return response()->json([
            'success' => true,
            'message' => 'Insight completed',
        ]);
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

