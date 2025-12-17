<?php

namespace App\Http\Controllers;

use App\Models\AddyInsight;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CardStackController extends Controller
{
    /**
     * Display Addy insight cards for the authenticated user
     */
    public function insights(Request $request)
    {
        $section = $request->get('section'); // Optional section filter
        
        $query = AddyInsight::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->where('is_dismissed', false)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc');
        
        if ($section) {
            $query->where('section', $section);
        }
        
        $insights = $query->limit(15)->get()->map(function ($insight) {
            return [
                'id' => $insight->id,
                'title' => $insight->title,
                'description' => $insight->description,
                'type' => $insight->type, // alert, suggestion, observation, achievement, tip
                'priority' => $insight->priority,
                'section' => $insight->section,
                'action_url' => $insight->url,
                'actions' => $insight->actions ?? [],
                'is_actionable' => $insight->is_actionable,
                'created_at' => $insight->created_at->diffForHumans(),
            ];
        });

        $statistics = $this->getInsightStatistics();

        return Inertia::render('CardStacks/InsightCardsPage', [
            'insights' => $insights,
            'statistics' => $statistics,
            'section' => $section,
        ]);
    }

    /**
     * Handle insight card dismissal
     */
    public function dismissInsight(Request $request, $insightId)
    {
        $validated = $request->validate([
            'direction' => 'required|in:left,right',
        ]);

        $insight = AddyInsight::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($insightId);

        // Right swipe = Acknowledge/Action taken
        // Left swipe = Dismiss
        $insight->update([
            'is_dismissed' => true,
            'dismissed_at' => now(),
            'dismissed_by' => Auth::id(),
        ]);

        // Award XP for engaging with insights
        if (config('features.gamification', true)) {
            try {
                $xpReason = $validated['direction'] === 'right' ? 'insight_actioned' : 'insight_reviewed';
                app(\App\Services\Addy\GamificationService::class)->awardXP(
                    Auth::id(),
                    $xpReason,
                    Auth::user()->organization_id,
                    ['insight_id' => $insight->id]
                );
            } catch (\Exception $e) {
                // Silently fail gamification
            }
        }

        return response()->json([
            'message' => $validated['direction'] === 'right' ? 'Insight actioned' : 'Insight dismissed',
            'action' => $validated['direction'] === 'right' ? 'actioned' : 'dismissed',
        ]);
    }

    /**
     * Display notification cards
     */
    public function notifications()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->where('organization_id', Auth::user()->organization_id)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'from' => $notification->from ?? 'System',
                    'timestamp' => $notification->created_at->diffForHumans(),
                    'icon' => $this->getNotificationIcon($notification->type),
                    'type' => $notification->type,
                    'action_url' => $notification->action_url,
                ];
            });

        return Inertia::render('CardStacks/NotificationCardsPage', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Handle notification card dismissal
     */
    public function dismissNotification(Request $request, Notification $notification)
    {
        $validated = $request->validate([
            'direction' => 'required|in:left,right',
        ]);

        if ($validated['direction'] === 'right') {
            // Accept/acknowledge notification
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
                'acknowledged_at' => now(),
            ]);

            return response()->json([
                'message' => 'Notification acknowledged',
                'action' => 'acknowledged',
            ]);
        } else {
            // Dismiss notification
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
                'dismissed_at' => now(),
            ]);

            return response()->json([
                'message' => 'Notification dismissed',
                'action' => 'dismissed',
            ]);
        }
    }

    /**
     * Get statistics for card stacks
     */
    public function statistics()
    {
        $stats = [
            'insights' => $this->getInsightStatistics()['insights'],
            'notifications' => [
                'unread' => Notification::where('user_id', Auth::id())
                    ->where('organization_id', Auth::user()->organization_id)
                    ->where('is_read', false)
                    ->count(),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Get insight statistics
     */
    protected function getInsightStatistics(): array
    {
        $organizationId = Auth::user()->organization_id;

        return [
            'insights' => [
                'active' => AddyInsight::where('organization_id', $organizationId)
                    ->where('is_active', true)
                    ->where('is_dismissed', false)
                    ->count(),
                'alerts' => AddyInsight::where('organization_id', $organizationId)
                    ->where('is_active', true)
                    ->where('is_dismissed', false)
                    ->where('type', 'alert')
                    ->count(),
                'suggestions' => AddyInsight::where('organization_id', $organizationId)
                    ->where('is_active', true)
                    ->where('is_dismissed', false)
                    ->where('type', 'suggestion')
                    ->count(),
                'reviewed_today' => AddyInsight::where('organization_id', $organizationId)
                    ->where('is_dismissed', true)
                    ->whereDate('dismissed_at', today())
                    ->count(),
            ],
        ];
    }

    /**
     * Get emoji icon for notification type
     */
    protected function getNotificationIcon(string $type): string
    {
        $icons = [
            'gamification_xp' => '⭐',
            'gamification_badge' => '🏆',
            'gamification_streak' => '🔥',
            'sale' => '💰',
            'invoice' => '📄',
            'payment' => '💳',
            'leave' => '🏖️',
            'expense' => '💸',
            'task' => '✅',
            'system' => '🔔',
            'insight' => '💡',
        ];

        return $icons[$type] ?? '🔔';
    }
}
