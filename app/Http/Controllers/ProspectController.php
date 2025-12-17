<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProspectController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        $prospects = Prospect::where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Prospects/Index', [
            'prospects' => $prospects,
        ]);
    }

    public function create()
    {
        $organization = Auth::user()->organization;
        
        return Inertia::render('Prospects/Create', [
            'organizationCurrency' => $organization->currency ?? 'ZMW',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'stage' => 'required|string',
            'estimated_value' => 'nullable|numeric',
            'probability' => 'nullable|integer|min:0|max:100',
            'currency' => 'nullable|string|max:3',
        ]);

        $organization = Auth::user()->organization;
        
        $validated['organization_id'] = Auth::user()->organization_id;
        
        // Set currency from organization if not provided
        if (empty($validated['currency'])) {
            $validated['currency'] = $organization->currency ?? 'ZMW';
        }

        Prospect::create($validated);

        return redirect()->route('prospects.index')
            ->with('success', 'Prospect created successfully.');
    }

    public function show(Prospect $prospect)
    {
        return Inertia::render('Prospects/Show', [
            'prospect' => $prospect,
        ]);
    }

    public function edit(Prospect $prospect)
    {
        return Inertia::render('Prospects/Edit', [
            'prospect' => $prospect,
        ]);
    }

    public function update(Request $request, Prospect $prospect)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'stage' => 'required|string',
            'estimated_value' => 'nullable|numeric',
            'probability' => 'nullable|integer|min:0|max:100',
            'currency' => 'nullable|string|max:3',
        ]);

        $organization = Auth::user()->organization;
        
        // Set currency from organization if not provided
        if (empty($validated['currency'])) {
            $validated['currency'] = $organization->currency ?? 'ZMW';
        }

        $prospect->update($validated);

        return redirect()->route('prospects.index')
            ->with('success', 'Prospect updated successfully.');
    }

    public function destroy(Prospect $prospect)
    {
        $prospect->delete();

        return redirect()->route('prospects.index')
            ->with('success', 'Prospect deleted successfully.');
    }

    public function convertToCustomer(Request $request, Prospect $prospect)
    {
        $validated = $request->validate([
            'payment_terms' => 'nullable|string',
            'credit_limit' => 'nullable|numeric',
        ]);

        $customer = $prospect->convertToCustomer($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Prospect converted to customer successfully.');
    }

    public function updateStage(Request $request, Prospect $prospect)
    {
        $validated = $request->validate([
            'stage' => 'required|string',
        ]);

        $prospect->moveToStage($validated['stage']);

        return redirect()->back()
            ->with('success', 'Prospect stage updated successfully.');
    }
}

