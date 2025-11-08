<?php

namespace App\Http\Controllers;

use App\Models\GoodsAndService;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StockAdjustmentController extends Controller
{
    public function create(Request $request)
    {
        $productId = $request->query('product_id');
        
        $products = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->where('type', 'product')
            ->where('track_stock', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Stock/AdjustmentCreate', [
            'products' => $products,
            'selectedProductId' => $productId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'goods_service_id' => 'required|uuid|exists:goods_and_services,id',
            'adjustment_type' => 'required|in:increase,decrease',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $product = GoodsAndService::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($validated['goods_service_id']);

            if (!$product->track_stock) {
                return back()->withErrors(['error' => 'Stock tracking is not enabled for this product']);
            }

            // Determine movement type
            $movementType = $validated['adjustment_type'] === 'increase' ? 'in' : 'out';

            // Create stock movement
            $movement = StockMovement::create([
                'id' => (string) Str::uuid(),
                'organization_id' => Auth::user()->organization_id,
                'goods_service_id' => $validated['goods_service_id'],
                'movement_type' => $movementType,
                'quantity' => $validated['quantity'],
                'reference_number' => 'ADJ-' . now()->format('Ymd-His'),
                'notes' => $validated['reason'] . ($validated['notes'] ? ': ' . $validated['notes'] : ''),
                'created_by_id' => Auth::id(),
            ]);

            // Update product stock
            if ($validated['adjustment_type'] === 'increase') {
                $product->increment('current_stock', $validated['quantity']);
            } else {
                $product->decrement('current_stock', $validated['quantity']);
            }

            DB::commit();

            return redirect()->route('stock.movements.show', $movement->id)->with('message', 'Stock adjustment recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to record adjustment: ' . $e->getMessage()]);
        }
    }
}

