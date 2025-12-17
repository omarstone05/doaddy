<?php

namespace App\Http\Controllers\PrintShop;

use App\Http\Controllers\Controller;
use App\Models\Print\PrintMaterial;
use App\Models\Print\InkConfiguration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrintMaterialController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->current_organization_id;

        $materials = PrintMaterial::where('organization_id', $businessId)
            ->with('inkConfigurations')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->type, fn($q) => $q->where('material_type', $request->type))
            ->when($request->has('active'), fn($q) => $q->where('is_active', $request->active === 'true'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $inkConfigurations = InkConfiguration::where('organization_id', $businessId)->get();

        return Inertia::render('PrintShop/Materials/Index', [
            'materials' => $materials,
            'inkConfigurations' => $inkConfigurations,
            'materialTypes' => PrintMaterial::getTypeOptions(),
            'filters' => $request->only(['search', 'type', 'active']),
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $request->user()->current_organization_id;
        $inkConfigurations = InkConfiguration::where('organization_id', $businessId)->get();

        return Inertia::render('PrintShop/Materials/Create', [
            'inkConfigurations' => $inkConfigurations,
            'materialTypes' => PrintMaterial::getTypeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'material_type' => 'required|in:vinyl,banner,banner_flex,contra_vision,clear_vinyl,custom',
            'roll_width' => 'required|numeric|min:0.01',
            'roll_length' => 'required|numeric|min:0.01',
            'material_cost' => 'required|numeric|min:0',
            'off_cut_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'ink_configuration_ids' => 'nullable|array',
            'ink_configuration_ids.*' => 'exists:ink_configurations,id',
        ]);

        $validated['organization_id'] = $request->user()->current_organization_id;
        $validated['off_cut_cost'] = $validated['off_cut_cost'] ?? 7.00;

        $material = PrintMaterial::create($validated);

        if (!empty($validated['ink_configuration_ids'])) {
            $material->inkConfigurations()->sync($validated['ink_configuration_ids']);
        }

        return redirect()->route('print-shop.materials.index')
            ->with('success', 'Material created successfully.');
    }

    public function edit(Request $request, PrintMaterial $material)
    {
        $this->authorize('view', $material);

        $businessId = $request->user()->current_organization_id;
        $inkConfigurations = InkConfiguration::where('organization_id', $businessId)->get();

        $material->load('inkConfigurations');

        return Inertia::render('PrintShop/Materials/Edit', [
            'material' => $material,
            'inkConfigurations' => $inkConfigurations,
            'materialTypes' => PrintMaterial::getTypeOptions(),
        ]);
    }

    public function update(Request $request, PrintMaterial $material)
    {
        $this->authorize('update', $material);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'material_type' => 'required|in:vinyl,banner,banner_flex,contra_vision,clear_vinyl,custom',
            'roll_width' => 'required|numeric|min:0.01',
            'roll_length' => 'required|numeric|min:0.01',
            'material_cost' => 'required|numeric|min:0',
            'off_cut_cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'ink_configuration_ids' => 'nullable|array',
            'ink_configuration_ids.*' => 'exists:ink_configurations,id',
        ]);

        $material->update($validated);

        if (isset($validated['ink_configuration_ids'])) {
            $material->inkConfigurations()->sync($validated['ink_configuration_ids']);
        }

        return redirect()->route('print-shop.materials.index')
            ->with('success', 'Material updated successfully.');
    }

    public function destroy(PrintMaterial $material)
    {
        $this->authorize('delete', $material);

        // Check if material is used in any jobs
        if ($material->printJobs()->exists()) {
            return back()->with('error', 'Cannot delete material that has been used in print jobs.');
        }

        $material->delete();

        return redirect()->route('print-shop.materials.index')
            ->with('success', 'Material deleted successfully.');
    }
}

