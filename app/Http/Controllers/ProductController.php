<?php

namespace App\Http\Controllers;

use App\Models\GoodsAndService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = GoodsAndService::where('organization_id', Auth::user()->organization_id);

        // Filters
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === 'true');
        }

        if ($request->has('low_stock') && $request->low_stock === 'true') {
            $query->where('track_stock', true)
                  ->whereColumn('current_stock', '<=', 'minimum_stock');
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Get unique categories for filter
        $categories = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $products = $query->orderBy('name')->paginate(20);
        
        // Add is_low_stock attribute to each product
        $products->getCollection()->transform(function ($product) {
            $product->is_low_stock = $product->isLowStock();
            return $product;
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $request->only(['type', 'category', 'is_active', 'low_stock', 'search']),
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Products/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:product,service',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
        ]);

        // Ensure current_stock defaults to 0 if not provided or empty
        if (!isset($validated['current_stock']) || $validated['current_stock'] === null || $validated['current_stock'] === '') {
            $validated['current_stock'] = 0;
        }
        
        // Ensure boolean fields have defaults
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }
        if (!isset($validated['track_stock'])) {
            $validated['track_stock'] = false;
        }

        $product = GoodsAndService::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            ...$validated,
        ]);

        return redirect()->route('products.show', $product->id)->with('message', 'Product created successfully');
    }

    public function show($id)
    {
        $product = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->with(['stockMovements' => function ($query) {
                $query->latest()->limit(10);
            }])
            ->findOrFail($id);

        return Inertia::render('Products/Show', [
            'product' => $product,
        ]);
    }

    public function edit($id)
    {
        $product = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        return Inertia::render('Products/Edit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:product,service',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
        ]);

        // Ensure current_stock is not null if provided (default to 0 if explicitly set to null)
        if (array_key_exists('current_stock', $validated) && $validated['current_stock'] === null) {
            $validated['current_stock'] = 0;
        }

        $product->update($validated);

        return redirect()->route('products.show', $product->id)->with('message', 'Product updated successfully');
    }

    public function destroy($id)
    {
        $product = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        // Check if product has been used in sales
        if ($product->saleItems()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete product that has been used in sales. Deactivate it instead.']);
        }

        $product->delete();

        return redirect()->route('products.index')->with('message', 'Product deleted successfully');
    }

    /**
     * Quick create product via API (for inline creation in forms)
     */
    public function quickCreate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:product,service',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
        ]);

        $product = GoodsAndService::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'cost_price' => $validated['cost_price'] ?? null,
            'selling_price' => $validated['selling_price'] ?? null,
            'current_stock' => $validated['current_stock'] ?? 0,
            'minimum_stock' => $validated['minimum_stock'] ?? null,
            'unit' => $validated['unit'] ?? null,
            'category' => $validated['category'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'track_stock' => $validated['track_stock'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'product' => $product,
        ]);
    }
}

