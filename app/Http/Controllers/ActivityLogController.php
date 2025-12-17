<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::where('organization_id', Auth::user()->organization_id)
            ->with('user');

        if ($request->has('user_id') && $request->user_id !== '') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action_type') && $request->action_type !== '') {
            $query->where('action_type', $request->action_type);
        }

        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activityLogs = $query->orderBy('created_at', 'desc')->paginate(50);

        $users = \App\Models\User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('ActivityLogs/Index', [
            'activityLogs' => $activityLogs,
            'users' => $users,
            'filters' => $request->only(['user_id', 'action_type', 'date_from', 'date_to']),
        ]);
    }
}

