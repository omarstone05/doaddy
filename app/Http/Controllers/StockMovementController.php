<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\GoodsAndService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::where('organization_id', Auth::user()->organization_id)
            ->with(['goodsService', 'createdBy']);

        if ($request->has('product_id') && $request->product_id !== '') {
            $query->where('goods_service_id', $request->product_id);
        }

        if ($request->has('movement_type') && $request->movement_type !== '') {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->latest()->paginate(50);

        // Get products for filter
        $products = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->where('type', 'product')
            ->where('track_stock', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Stock/Movements', [
            'movements' => $movements,
            'products' => $products,
            'filters' => $request->only(['product_id', 'movement_type', 'date_from', 'date_to']),
        ]);
    }

    public function show($id)
    {
        $movement = StockMovement::where('organization_id', Auth::user()->organization_id)
            ->with(['goodsService', 'createdBy'])
            ->findOrFail($id);

        return Inertia::render('Stock/MovementShow', [
            'movement' => $movement,
        ]);
    }
}

