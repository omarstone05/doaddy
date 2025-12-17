<?php

namespace App\Http\Controllers\PrintShop;

use App\Http\Controllers\Controller;
use App\Models\Print\InkConfiguration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InkConfigurationController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->current_organization_id;

        $inkConfigurations = InkConfiguration::where('organization_id', $businessId)
            ->withCount('materials')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PrintShop/InkConfigs/Index', [
            'inkConfigurations' => $inkConfigurations,
        ]);
    }

    public function create()
    {
        return Inertia::render('PrintShop/InkConfigs/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bottles_per_set' => 'required|integer|min:1',
            'cost_per_set' => 'required|numeric|min:0',
            'coverage_area' => 'required|numeric|min:0.01',
            'coverage_multiplier' => 'nullable|integer|min:1',
            'is_default' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['organization_id'] = $request->user()->current_organization_id;
        $validated['coverage_multiplier'] = $validated['coverage_multiplier'] ?? 1;

        $inkConfig = InkConfiguration::create($validated);

        if ($validated['is_default'] ?? false) {
            $inkConfig->setAsDefault();
        }

        return redirect()->route('print-shop.ink-configs.index')
            ->with('success', 'Ink configuration created successfully.');
    }

    public function edit(InkConfiguration $inkConfig)
    {
        $this->authorize('view', $inkConfig);

        return Inertia::render('PrintShop/InkConfigs/Edit', [
            'inkConfiguration' => $inkConfig,
        ]);
    }

    public function update(Request $request, InkConfiguration $inkConfig)
    {
        $this->authorize('update', $inkConfig);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bottles_per_set' => 'required|integer|min:1',
            'cost_per_set' => 'required|numeric|min:0',
            'coverage_area' => 'required|numeric|min:0.01',
            'coverage_multiplier' => 'nullable|integer|min:1',
            'is_default' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $inkConfig->update($validated);

        if ($validated['is_default'] ?? false) {
            $inkConfig->setAsDefault();
        }

        return redirect()->route('print-shop.ink-configs.index')
            ->with('success', 'Ink configuration updated successfully.');
    }

    public function destroy(InkConfiguration $inkConfig)
    {
        $this->authorize('delete', $inkConfig);

        // Check if used in any materials
        if ($inkConfig->materials()->exists()) {
            return back()->with('error', 'Cannot delete ink configuration that is mapped to materials.');
        }

        $inkConfig->delete();

        return redirect()->route('print-shop.ink-configs.index')
            ->with('success', 'Ink configuration deleted successfully.');
    }

    public function setDefault(InkConfiguration $inkConfig)
    {
        $this->authorize('update', $inkConfig);
        
        $inkConfig->setAsDefault();

        return back()->with('success', 'Default ink configuration updated.');
    }
}

