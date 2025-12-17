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

    public function updateLayout(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $layouts = $request->input('layouts', []);
        
        foreach ($layouts as $layout) {
            OrgDashboardCard::where('organization_id', $organizationId)
                ->where('id', $layout['id'])
                ->update([
                    'row' => $layout['row'] ?? 0,
                    'col' => $layout['col'] ?? 0,
                    'width' => $layout['width'] ?? 8, // Default 8x8
                    'height' => $layout['height'] ?? 8, // Default 8x8
                    'display_order' => $layout['display_order'] ?? 0,
                ]);
        }
        
        return response()->json(['success' => true]);
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
        
        // Check if card already exists for this organization
        $existingCard = OrgDashboardCard::where('organization_id', $organizationId)
            ->where('dashboard_card_id', $dashboardCardId)
            ->first();
        
        if ($existingCard) {
            // If card exists but is hidden, make it visible again
            if (!$existingCard->is_visible) {
                $existingCard->update(['is_visible' => true]);
            }
            // Otherwise, card already exists and is visible - do nothing
            return back();
        }
        
        // Card doesn't exist, create new one
        $maxOrder = OrgDashboardCard::where('organization_id', $organizationId)
            ->max('display_order') ?? 0;
        
        // Calculate default position
        // Try to place small cards (width <= 4) in row 0, col 8-11 (next to Addy card)
        // Otherwise place in next available row
        $existingCards = OrgDashboardCard::where('organization_id', $organizationId)
            ->where('is_visible', true)
            ->get();
        
        // Check if we can fit in row 0 (after Addy card at col 0-7)
        $cardsInRow0 = $existingCards->filter(function($card) {
            return $card->row === 0 && $card->col >= 8;
        });
        
        $usedColsInRow0 = $cardsInRow0->reduce(function($max, $card) {
            return max($max, $card->col + $card->width);
        }, 8);
        
        $defaultWidth = 8; // Default card width (8x8)
        $defaultHeight = 8; // Default card height (8x8)
        
        // Position will be calculated by frontend auto-layout
        // Just set defaults here
        $row = 0;
        $col = 0;
        
        OrgDashboardCard::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'dashboard_card_id' => $dashboardCardId,
            'display_order' => $maxOrder + 1,
            'is_visible' => true,
            'row' => $row,
            'col' => $col,
            'width' => $defaultWidth,
            'height' => $defaultHeight,
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
