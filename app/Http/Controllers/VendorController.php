<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        $vendors = Vendor::where('organization_id', $organizationId)
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
        ]);
    }

    public function create()
    {
        return Inertia::render('Vendors/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'currency' => 'nullable|string|max:3',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        $validated['status'] = 'active';

        Vendor::create($validated);

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor)
    {
        return Inertia::render('Vendors/Show', [
            'vendor' => $vendor,
        ]);
    }

    public function edit(Vendor $vendor)
    {
        return Inertia::render('Vendors/Edit', [
            'vendor' => $vendor,
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'currency' => 'nullable|string|max:3',
            'status' => 'nullable|string',
        ]);

        $vendor->update($validated);

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor deleted successfully.');
    }
}

