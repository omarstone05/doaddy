<?php

namespace App\Http\Controllers;

use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class MoneyController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Calculate stats
        $totalAccounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->count();
        
        $totalBalance = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->sum('current_balance');
        
        $thisMonthStart = Carbon::now()->startOfMonth();
        
        $monthlyIncome = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->where('transaction_date', '>=', $thisMonthStart)
            ->sum('amount');
        
        $monthlyExpenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->where('transaction_date', '>=', $thisMonthStart)
            ->sum('amount');
        
        // Get recent movements
        $recentMovements = MoneyMovement::where('organization_id', $organizationId)
            ->with('fromAccount')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'description' => $movement->description,
                    'amount' => $movement->amount,
                    'flow_type' => $movement->flow_type,
                    'transaction_date' => $movement->transaction_date,
                    'account' => $movement->fromAccount ? [
                        'name' => $movement->fromAccount->name,
                    ] : null,
                ];
            });
        
        // Get Money-specific insights
        $insights = AddyInsight::active($organizationId)
            ->where('category', 'money')
            ->limit(3)
            ->get()
            ->map(fn($insight) => [
                'id' => $insight->id,
                'type' => $insight->type,
                'title' => $insight->title,
                'description' => $insight->description,
                'priority' => (float) $insight->priority,
                'is_actionable' => $insight->is_actionable,
                'action_url' => $insight->action_url,
            ]);

        return Inertia::render('Money/Index', [
            'stats' => [
                'total_accounts' => $totalAccounts,
                'total_balance' => $totalBalance,
                'monthly_income' => $monthlyIncome,
                'monthly_expenses' => $monthlyExpenses,
                'recent_movements' => $recentMovements,
            ],
            'insights' => $insights,
        ]);
    }
}

