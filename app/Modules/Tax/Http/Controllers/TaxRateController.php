<?php

namespace App\Modules\Tax\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tax\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxRateController extends Controller
{
    /**
     * Get all tax rates for the current organization
     */
    public function index(Request $request)
    {
        $organizationId = $request->user()->current_organization_id;
        
        $taxRates = TaxRate::where('organization_id', $organizationId)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json($taxRates);
    }

    /**
     * Store a new tax rate
     */
    public function store(Request $request)
    {
        $organizationId = $request->user()->current_organization_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'tax_type' => 'nullable|string|in:vat,sales_tax,gst,custom',
        ]);

        $taxRate = TaxRate::create([
            'organization_id' => $organizationId,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'rate' => $validated['rate'],
            'description' => $validated['description'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'tax_type' => $validated['tax_type'] ?? 'vat',
        ]);

        return response()->json($taxRate, 201);
    }

    /**
     * Update a tax rate
     */
    public function update(Request $request, TaxRate $taxRate)
    {
        $organizationId = $request->user()->current_organization_id;

        // Ensure tax rate belongs to user's organization
        if ($taxRate->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this tax rate.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'tax_type' => 'nullable|string|in:vat,sales_tax,gst,custom',
        ]);

        $taxRate->update($validated);

        return response()->json($taxRate);
    }

    /**
     * Delete a tax rate
     */
    public function destroy(Request $request, TaxRate $taxRate)
    {
        $organizationId = $request->user()->current_organization_id;

        // Ensure tax rate belongs to user's organization
        if ($taxRate->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this tax rate.');
        }

        // Prevent deletion of default tax rate if it's the only one
        $activeRates = TaxRate::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->count();

        if ($taxRate->is_default && $activeRates === 1) {
            return response()->json([
                'error' => 'Cannot delete the only active tax rate. Please create another tax rate first or deactivate this one instead.',
            ], 422);
        }

        $taxRate->delete();

        return response()->json(['message' => 'Tax rate deleted successfully']);
    }
}


