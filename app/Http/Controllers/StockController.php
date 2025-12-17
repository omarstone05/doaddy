<?php

namespace App\Http\Controllers;

use App\Models\GoodsAndService;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->where('type', 'product')
            ->where('track_stock', true);

        if ($request->has('low_stock') && $request->low_stock === 'true') {
            $query->whereColumn('current_stock', '<=', 'minimum_stock');
        }

        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Get categories for filter
        $categories = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        // Calculate totals
        $totalProducts = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->where('type', 'product')
            ->where('track_stock', true)
            ->count();

        $lowStockCount = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->where('type', 'product')
            ->where('track_stock', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();

        $totalStockValue = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->where('type', 'product')
            ->where('track_stock', true)
            ->sum(DB::raw('current_stock * cost_price'));

        $products = $query->orderBy('name')->paginate(20);
        
        // Add is_low_stock attribute to each product
        $products->getCollection()->transform(function ($product) {
            $product->is_low_stock = $product->isLowStock();
            return $product;
        });

        return Inertia::render('Stock/Index', [
            'products' => $products,
            'filters' => $request->only(['low_stock', 'category', 'search']),
            'categories' => $categories,
            'stats' => [
                'total_products' => $totalProducts,
                'low_stock_count' => $lowStockCount,
                'total_stock_value' => $totalStockValue,
            ],
        ]);
    }
}

