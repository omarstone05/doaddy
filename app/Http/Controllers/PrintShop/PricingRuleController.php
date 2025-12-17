<?php

namespace App\Http\Controllers\PrintShop;

use App\Http\Controllers\Controller;
use App\Models\Print\PricingRule;
use App\Models\Print\PrintMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PricingRuleController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->current_organization_id;

        $pricingRules = PricingRule::where('organization_id', $businessId)
            ->with('printMaterial')
            ->orderByDesc('priority')
            ->orderByDesc('is_default')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PrintShop/PricingRules/Index', [
            'pricingRules' => $pricingRules,
            'markupTypes' => PricingRule::getMarkupTypeOptions(),
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $request->user()->current_organization_id;
        $materials = PrintMaterial::where('organization_id', $businessId)->where('is_active', true)->get();

        return Inertia::render('PrintShop/PricingRules/Create', [
            'materials' => $materials,
            'markupTypes' => PricingRule::getMarkupTypeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rule_name' => 'required|string|max:255',
            'print_material_id' => 'nullable|exists:print_materials,id',
            'markup_type' => 'required|in:percentage,fixed_amount,fixed_price',
            'markup_value' => 'required|numeric|min:0',
            'min_area' => 'nullable|numeric|min:0',
            'max_area' => 'nullable|numeric|min:0|gt:min_area',
            'is_default' => 'boolean',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['organization_id'] = $request->user()->current_organization_id;
        $validated['priority'] = $validated['priority'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // If setting as default, remove default from others
        if ($validated['is_default'] ?? false) {
            PricingRule::where('organization_id', $validated['organization_id'])
                ->update(['is_default' => false]);
        }

        PricingRule::create($validated);

        return redirect()->route('print-shop.pricing-rules.index')
            ->with('success', 'Pricing rule created successfully.');
    }

    public function edit(Request $request, PricingRule $pricingRule)
    {
        $this->authorize('view', $pricingRule);

        $businessId = $request->user()->current_organization_id;
        $materials = PrintMaterial::where('organization_id', $businessId)->where('is_active', true)->get();

        return Inertia::render('PrintShop/PricingRules/Edit', [
            'pricingRule' => $pricingRule,
            'materials' => $materials,
            'markupTypes' => PricingRule::getMarkupTypeOptions(),
        ]);
    }

    public function update(Request $request, PricingRule $pricingRule)
    {
        $this->authorize('update', $pricingRule);

        $validated = $request->validate([
            'rule_name' => 'required|string|max:255',
            'print_material_id' => 'nullable|exists:print_materials,id',
            'markup_type' => 'required|in:percentage,fixed_amount,fixed_price',
            'markup_value' => 'required|numeric|min:0',
            'min_area' => 'nullable|numeric|min:0',
            'max_area' => 'nullable|numeric|min:0',
            'is_default' => 'boolean',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // If setting as default, remove default from others
        if (($validated['is_default'] ?? false) && !$pricingRule->is_default) {
            PricingRule::where('organization_id', $pricingRule->organization_id)
                ->where('id', '!=', $pricingRule->id)
                ->update(['is_default' => false]);
        }

        $pricingRule->update($validated);

        return redirect()->route('print-shop.pricing-rules.index')
            ->with('success', 'Pricing rule updated successfully.');
    }

    public function destroy(PricingRule $pricingRule)
    {
        $this->authorize('delete', $pricingRule);

        $pricingRule->delete();

        return redirect()->route('print-shop.pricing-rules.index')
            ->with('success', 'Pricing rule deleted successfully.');
    }
}

