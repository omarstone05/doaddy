<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\RegisterSession;
use App\Models\TeamMember;
use App\Models\GoodsAndService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.goods_service_id' => 'required|uuid|exists:goods_and_services,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,mobile_money,card,credit',
            'payment_reference' => 'nullable|string|max:255',
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'money_account_id' => 'required|uuid|exists:money_accounts,id',
            'register_session_id' => 'nullable|uuid|exists:register_sessions,id',
        ]);

        $organizationId = Auth::user()->organization_id;
        
        // Get cashier (team member) - create if doesn't exist
        $cashier = TeamMember::where('organization_id', $organizationId)
            ->where('user_id', Auth::id())
            ->first();
        
        if (!$cashier) {
            // Create a team member for the user if doesn't exist
            $cashier = TeamMember::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'user_id' => Auth::id(),
                'first_name' => Auth::user()->name,
                'last_name' => '',
                'is_active' => true,
            ]);
        }

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = 0;
            $items = [];
            
            foreach ($validated['items'] as $itemData) {
                $product = GoodsAndService::where('organization_id', $organizationId)
                    ->findOrFail($itemData['goods_service_id']);
                
                $quantity = $itemData['quantity'];
                $unitPrice = $product->selling_price ?? 0;
                $total = $quantity * $unitPrice;
                
                $subtotal += $total;
                
                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                    'cost_price' => $product->cost_price,
                ];
            }

            $taxAmount = 0; // Can be calculated if needed
            $discountAmount = 0; // Can be added if needed
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Create sale
            $sale = Sale::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'money_account_id' => $validated['money_account_id'],
                'cashier_id' => $cashier->id,
                'register_session_id' => $validated['register_session_id'] ?? null,
                'status' => 'completed',
                'sale_date' => now()->toDateString(),
            ]);

            // Create sale items
            foreach ($items as $index => $item) {
                SaleItem::create([
                    'id' => (string) Str::uuid(),
                    'sale_id' => $sale->id,
                    'goods_service_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'sku' => $item['product']->sku,
                    'barcode' => $item['product']->barcode,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    'cost_price' => $item['cost_price'],
                    'display_order' => $index,
                ]);
            }

            // Reload sale with items and relationships
            $sale->load(['items', 'customer', 'cashier', 'moneyAccount']);

            DB::commit();

            // Return JSON response for Inertia to handle redirect
            return Inertia::render('POS/Receipt', [
                'sale' => $sale,
            ])->with('message', 'Sale recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to record sale: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $sale = Sale::where('organization_id', Auth::user()->organization_id)
            ->with(['items', 'customer', 'cashier', 'moneyAccount'])
            ->findOrFail($id);

        return Inertia::render('POS/Receipt', [
            'sale' => $sale,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $organizationId = Auth::user()->organization_id;
        
        $sales = Sale::where('organization_id', $organizationId)
            ->where(function ($q) use ($query) {
                $q->where('sale_number', 'like', "%{$query}%")
                  ->orWhere('customer_name', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();
        
        return response()->json($sales);
    }
}
