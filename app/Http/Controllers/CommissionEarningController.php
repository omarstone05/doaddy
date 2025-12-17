<?php

namespace App\Http\Controllers;

use App\Models\CommissionEarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CommissionEarningController extends Controller
{
    public function index(Request $request)
    {
        $query = CommissionEarning::where('organization_id', Auth::user()->organization_id)
            ->with(['teamMember', 'sale', 'commissionRule']);

        if ($request->has('team_member_id') && $request->team_member_id !== '') {
            $query->where('team_member_id', $request->team_member_id);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $earnings = $query->orderBy('created_at', 'desc')->paginate(20);

        $totalPending = CommissionEarning::where('organization_id', Auth::user()->organization_id)
            ->where('status', 'pending')
            ->sum('amount');

        $totalPaid = CommissionEarning::where('organization_id', Auth::user()->organization_id)
            ->where('status', 'paid')
            ->sum('amount');

        $teamMembers = \App\Models\TeamMember::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Commissions/Earnings/Index', [
            'earnings' => $earnings,
            'totalPending' => $totalPending,
            'totalPaid' => $totalPaid,
            'teamMembers' => $teamMembers,
            'filters' => $request->only(['team_member_id', 'status', 'date_from', 'date_to']),
        ]);
    }
}

