<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\OrganizationRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::where('user_id', Auth::id())
            ->where('organization_id', Auth::user()->organization_id);

        if ($request->has('is_read') && $request->is_read !== '') {
            $query->where('is_read', $request->is_read === 'true');
        }

        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        $unreadCount = Notification::where('user_id', Auth::id())
            ->where('organization_id', Auth::user()->organization_id)
            ->where('is_read', false)
            ->count();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filters' => $request->only(['is_read', 'type']),
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('message', 'Notification marked as read');
    }

    public function destroy($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $notification->delete();

        return back()->with('message', 'Notification deleted');
    }

    public function recent()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->where('organization_id', Auth::user()->organization_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'action_url' => $notification->action_url,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'created_at_full' => $notification->created_at->toISOString(),
                ];
            });

        $unreadCount = Notification::where('user_id', Auth::id())
            ->where('organization_id', Auth::user()->organization_id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('organization_id', Auth::user()->organization_id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Accept an actionable notification (e.g., org invitation)
     */
    public function accept(Notification $notification, Request $request)
    {
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            abort(403, 'You do not have access to this notification.');
        }

        // Attach user to the organization if needed
        if ($notification->organization_id) {
            $orgId = $notification->organization_id;

            if (!$user->belongsToOrganization($orgId)) {
                $defaultRole = OrganizationRole::where('slug', 'member')->first();

                $user->organizations()->attach($orgId, [
                    'role_id' => $defaultRole?->id,
                    'role' => $defaultRole?->slug ?? 'member',
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }

            session(['current_organization_id' => $orgId]);
            $user->update(['organization_id' => $orgId]);
        }

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        $redirectTo = $request->session()->pull('post_login_redirect')
            ?? $request->session()->get('url.intended')
            ?? '/dashboard';

        return redirect($redirectTo)->with('message', 'Invitation accepted. You now have access.');
    }
}
