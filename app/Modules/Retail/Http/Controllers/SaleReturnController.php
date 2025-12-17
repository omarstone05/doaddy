<?php

namespace App\Modules\Retail\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Retail\Models\SaleReturn;
use App\Modules\Retail\Models\SaleReturnItem;
use App\Modules\Retail\Models\Sale;
use App\Modules\Retail\Models\SaleItem;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = SaleReturn::where('organization_id', Auth::user()->organization_id)
            ->with(['sale', 'processedBy'])
            ->orderBy('return_date', 'desc')
            ->paginate(20);

        return Inertia::render('Retail/SaleReturns/Index', [
            'returns' => $returns,
        ]);
    }

    public function create(Request $request)
    {
        $saleId = $request->query('sale_id');
        $sale = null;
        
        if ($saleId) {
            $sale = Sale::where('organization_id', Auth::user()->organization_id)
                ->with(['items.goodsService', 'customer'])
                ->find($saleId);
        }

        return Inertia::render('Retail/SaleReturns/Create', [
            'sale' => $sale,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|uuid|exists:sales,id',
            'return_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|uuid|exists:sale_items,id',
            'items.*.quantity_returned' => 'required|numeric|min:0.01',
            'return_reason' => 'nullable|string|max:255',
            'refund_method' => 'required|in:cash,mobile_money,card,credit_note',
            'refund_reference' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Calculate total return amount
            $returnAmount = 0;
            foreach ($validated['items'] as $item) {
                $saleItem = SaleItem::findOrFail($item['sale_item_id']);
                $returnAmount += $saleItem->total * ($item['quantity_returned'] / $saleItem->quantity);
            }

            // Get team member
            $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$teamMember) {
                $teamMember = TeamMember::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => Auth::user()->organization_id,
                    'user_id' => Auth::id(),
                    'first_name' => Auth::user()->name,
                    'last_name' => '',
                    'is_active' => true,
                ]);
            }

            // Create return
            $return = SaleReturn::create([
                'id' => (string) Str::uuid(),
                'organization_id' => Auth::user()->organization_id,
                'sale_id' => $validated['sale_id'],
                'return_amount' => $returnAmount,
                'return_reason' => $validated['return_reason'] ?? null,
                'refund_method' => $validated['refund_method'],
                'refund_reference' => $validated['refund_reference'] ?? null,
                'processed_by_id' => $teamMember->id,
                'status' => 'completed',
                'return_date' => $validated['return_date'],
            ]);

            // Create return items
            foreach ($validated['items'] as $item) {
                $saleItem = SaleItem::findOrFail($item['sale_item_id']);
                $refundAmount = $saleItem->total * ($item['quantity_returned'] / $saleItem->quantity);
                
                SaleReturnItem::create([
                    'id' => (string) Str::uuid(),
                    'sale_return_id' => $return->id,
                    'sale_item_id' => $item['sale_item_id'],
                    'quantity_returned' => $item['quantity_returned'],
                    'refund_amount' => $refundAmount,
                ]);
            }

            DB::commit();

            return redirect()->route('retail.sale-returns.show', $return->id)->with('message', 'Return processed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process return: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $return = SaleReturn::where('organization_id', Auth::user()->organization_id)
            ->with(['sale.customer', 'sale.items.goodsService', 'items.saleItem', 'processedBy'])
            ->findOrFail($id);

        return Inertia::render('Retail/SaleReturns/Show', [
            'return' => $return,
        ]);
    }
}

