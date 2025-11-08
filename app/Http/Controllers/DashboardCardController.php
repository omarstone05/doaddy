<?php

namespace App\Http\Controllers;

use App\Models\DashboardCard;
use App\Models\OrgDashboardCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardCardController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Get all available cards
        $availableCards = DashboardCard::where('is_active', true)->get();
        
        // Get organization's configured cards
        $orgCards = OrgDashboardCard::where('organization_id', $organizationId)
            ->with('dashboardCard')
            ->orderBy('display_order')
            ->get();
        
        return Inertia::render('Dashboard', [
            'availableCards' => $availableCards,
            'orgCards' => $orgCards,
        ]);
    }

    public function updateOrder(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $orders = $request->input('orders', []);
        
        foreach ($orders as $order) {
            OrgDashboardCard::where('organization_id', $organizationId)
                ->where('id', $order['id'])
                ->update(['display_order' => $order['order']]);
        }
        
        return back();
    }

    public function toggleVisibility(Request $request, $id)
    {
        $orgCard = OrgDashboardCard::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);
        
        $orgCard->update(['is_visible' => !$orgCard->is_visible]);
        
        return back();
    }

    public function addCard(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $dashboardCardId = $request->input('dashboard_card_id');
        
        $maxOrder = OrgDashboardCard::where('organization_id', $organizationId)
            ->max('display_order') ?? 0;
        
        OrgDashboardCard::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'dashboard_card_id' => $dashboardCardId,
            'display_order' => $maxOrder + 1,
            'is_visible' => true,
        ]);
        
        return back();
    }

    public function removeCard($id)
    {
        OrgDashboardCard::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id)
            ->delete();
        
        return back();
    }
}
