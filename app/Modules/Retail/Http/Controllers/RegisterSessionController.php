<?php

namespace App\Modules\Retail\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Retail\Models\RegisterSession;
use App\Modules\Retail\Models\Sale;
use App\Models\MoneyAccount;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegisterSessionController extends Controller
{
    public function index()
    {
        $organizationId = Auth::user()->organization_id;

        // Get current open session
        $openSession = RegisterSession::where('organization_id', $organizationId)
            ->where('status', 'open')
            ->with(['openedBy', 'moneyAccount'])
            ->first();

        // Get recent closed sessions
        $closedSessions = RegisterSession::where('organization_id', $organizationId)
            ->where('status', 'closed')
            ->with(['openedBy', 'closedBy', 'moneyAccount'])
            ->orderBy('closing_date', 'desc')
            ->limit(10)
            ->get();

        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('type', 'cash')
            ->where('is_active', true)
            ->get();

        return Inertia::render('Retail/Register/Index', [
            'openSession' => $openSession,
            'closedSessions' => $closedSessions,
            'accounts' => $accounts,
        ]);
    }

    public function open(Request $request)
    {
        $validated = $request->validate([
            'money_account_id' => 'required|uuid|exists:money_accounts,id',
            'opening_float' => 'required|numeric|min:0',
        ]);

        $organizationId = Auth::user()->organization_id;

        // Check if there's already an open session
        $existingSession = RegisterSession::where('organization_id', $organizationId)
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            return back()->withErrors(['error' => 'There is already an open register session']);
        }

        // Get team member
        $teamMember = TeamMember::where('organization_id', $organizationId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$teamMember) {
            $teamMember = TeamMember::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'user_id' => Auth::id(),
                'first_name' => Auth::user()->name,
                'last_name' => '',
                'is_active' => true,
            ]);
        }

        $session = RegisterSession::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'money_account_id' => $validated['money_account_id'],
            'opened_by_id' => $teamMember->id,
            'opening_date' => now(),
            'opening_float' => $validated['opening_float'],
            'status' => 'open',
        ]);

        return redirect()->route('retail.register.index')->with('message', 'Register session opened successfully');
    }

    public function close(Request $request, $id)
    {
        $session = RegisterSession::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        if ($session->status !== 'open') {
            return back()->withErrors(['error' => 'Session is not open']);
        }

        $validated = $request->validate([
            'closing_count' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Calculate sales totals
            $sales = Sale::where('register_session_id', $session->id)
                ->where('status', 'completed')
                ->get();

            $cashSales = $sales->where('payment_method', 'cash')->sum('total_amount');
            $mobileMoneySales = $sales->where('payment_method', 'mobile_money')->sum('total_amount');
            $cardSales = $sales->where('payment_method', 'card')->sum('total_amount');
            $creditSales = $sales->where('payment_method', 'credit')->sum('total_amount');
            $totalSales = $sales->sum('total_amount');

            // Calculate expected cash
            $expectedCash = $session->opening_float + $cashSales - $session->cash_paid_out + $session->cash_received;

            // Calculate variance
            $variance = $validated['closing_count'] - $expectedCash;

            // Get team member for closer
            $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
                ->where('user_id', Auth::id())
                ->first();

            $session->update([
                'closed_by_id' => $teamMember->id,
                'closing_date' => now(),
                'closing_count' => $validated['closing_count'],
                'expected_cash' => $expectedCash,
                'variance' => $variance,
                'total_sales' => $totalSales,
                'cash_sales' => $cashSales,
                'mobile_money_sales' => $mobileMoneySales,
                'card_sales' => $cardSales,
                'credit_sales' => $creditSales,
                'status' => 'closed',
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('retail.register.index')->with('message', 'Register session closed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to close session: ' . $e->getMessage()]);
        }
    }
}

