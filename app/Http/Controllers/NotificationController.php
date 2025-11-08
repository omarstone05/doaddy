<?php

namespace App\Http\Controllers;

use App\Models\Notification;
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
}

