<?php

namespace App\Http\Controllers;

use App\Models\GoodsAndService;
use App\Models\StockMovement;
use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Calculate stats
        $totalProducts = GoodsAndService::where('organization_id', $organizationId)
            ->where('type', 'product')
            ->count();
        
        $totalStockValue = GoodsAndService::where('organization_id', $organizationId)
            ->where('type', 'product')
            ->where('track_stock', true)
            ->select(DB::raw('SUM(current_stock * cost_price) as total'))
            ->value('total') ?? 0;
        
        $lowStockItems = GoodsAndService::where('organization_id', $organizationId)
            ->where('type', 'product')
            ->where('track_stock', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();
        
        $stockMovements = StockMovement::where('organization_id', $organizationId)
            ->count();
        
        // Get Inventory-specific insights
        $insights = AddyInsight::active($organizationId)
            ->where('category', 'inventory')
            ->orderBy('priority', 'desc')
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

        return Inertia::render('Inventory/Index', [
            'stats' => [
                'total_products' => $totalProducts,
                'total_stock_value' => 'K' . number_format($totalStockValue, 2),
                'low_stock_items' => $lowStockItems,
                'stock_movements' => $stockMovements,
            ],
            'insights' => $insights,
        ]);
    }
}

