<?php

namespace App\Http\Controllers;

use App\Models\GoodsAndService;
use App\Models\MoneyAccount;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TeamMember;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class POSController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Get current open session
        $session = RegisterSession::where('organization_id', $organizationId)
            ->where('status', 'open')
            ->first();
        
        // Get active products
        $products = GoodsAndService::where('organization_id', $organizationId)
            ->where('type', 'product')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Get cash account
        $cashAccount = MoneyAccount::where('organization_id', $organizationId)
            ->where('type', 'cash')
            ->where('is_active', true)
            ->first();
        
        // Get current user's team member - create if doesn't exist
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

        return Inertia::render('POS/Index', [
            'session' => $session,
            'products' => $products,
            'cashAccount' => $cashAccount,
            'teamMember' => $teamMember,
        ]);
    }

    public function searchCustomers(Request $request)
    {
        $query = $request->input('q', '');
        $organizationId = Auth::user()->organization_id;
        
        $customers = Customer::where('organization_id', $organizationId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();
        
        return response()->json($customers);
    }

    public function searchProducts(Request $request)
    {
        $query = $request->input('q', '');
        $organizationId = Auth::user()->organization_id;
        
        $products = GoodsAndService::where('organization_id', $organizationId)
            ->where('type', 'product')
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();
        
        return response()->json($products);
    }

    public function findByBarcode(Request $request, $barcode)
    {
        $organizationId = Auth::user()->organization_id;
        
        $product = GoodsAndService::where('organization_id', $organizationId)
            ->where('barcode', $barcode)
            ->where('type', 'product')
            ->where('is_active', true)
            ->first();
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        
        return response()->json($product);
    }
}
