<?php

namespace App\Http\Controllers;

use App\Models\AddyAction;
use App\Services\Addy\ActionExecutionService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AddyActionController extends Controller
{
    use AuthorizesRequests;

    public function confirm(Request $request, AddyAction $action)
    {
        // Manual authorization check
        if ($action->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        if ($action->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Action is not in pending status',
            ], 400);
        }

        $service = new ActionExecutionService(
            $action->organization,
            $request->user()
        );

        try {
            // Confirm
            $service->confirmAction($action);

            // Execute immediately
            $result = $service->executeAction($action);

            return response()->json([
                'success' => true,
                'result' => $result,
                'message' => $result['message'] ?? 'Action completed successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel(Request $request, AddyAction $action)
    {
        // Manual authorization check
        if ($action->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        if ($action->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Action is not in pending status',
            ], 400);
        }

        $service = new ActionExecutionService(
            $action->organization,
            $request->user()
        );

        $service->rejectAction($action, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Action cancelled.',
        ]);
    }

    public function rate(Request $request, AddyAction $action)
    {
        // Manual authorization check
        if ($action->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        if ($action->status !== 'executed') {
            return response()->json([
                'success' => false,
                'message' => 'Can only rate executed actions',
            ], 400);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $action->update(['user_rating' => $request->rating]);

        // Update pattern
        $pattern = \App\Models\AddyActionPattern::getOrCreate(
            $action->organization_id,
            $action->user_id,
            $action->action_type
        );
        $pattern->recordSuccess($request->rating);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!',
        ]);
    }

    public function history(Request $request)
    {
        $organization = $request->user()->organization;
        
        $actions = AddyAction::where('organization_id', $organization->id)
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($actions);
    }

    public function suggestions(Request $request)
    {
        $organization = $request->user()->organization;
        
        $service = new ActionExecutionService(
            $organization,
            $request->user()
        );

        $suggestions = $service->getSuggestedActions();

        return response()->json($suggestions);
    }
}

