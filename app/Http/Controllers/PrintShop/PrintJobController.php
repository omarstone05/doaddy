<?php

namespace App\Http\Controllers\PrintShop;

use App\Http\Controllers\Controller;
use App\Models\Print\PrintJob;
use App\Models\Print\PrintMaterial;
use App\Models\Print\InkConfiguration;
use App\Models\Print\PricingRule;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PrintJobController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->current_organization_id;

        $jobs = PrintJob::where('organization_id', $businessId)
            ->with(['customer', 'printMaterial', 'inkConfiguration'])
            ->when($request->search, fn($q) => $q->where('job_number', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->material_id, fn($q) => $q->where('print_material_id', $request->material_id))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $materials = PrintMaterial::where('organization_id', $businessId)->where('is_active', true)->get();

        return Inertia::render('PrintShop/Jobs/Index', [
            'jobs' => $jobs,
            'materials' => $materials,
            'statusOptions' => PrintJob::getStatusOptions(),
            'filters' => $request->only(['search', 'status', 'material_id']),
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $request->user()->current_organization_id;

        $materials = PrintMaterial::where('organization_id', $businessId)
            ->where('is_active', true)
            ->with('inkConfigurations')
            ->get();

        $customers = Customer::where('organization_id', $businessId)->orderBy('name')->get();

        return Inertia::render('PrintShop/Jobs/Create', [
            'materials' => $materials,
            'customers' => $customers,
            'statusOptions' => PrintJob::getStatusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'print_material_id' => 'required|exists:print_materials,id',
            'ink_configuration_id' => 'required|exists:ink_configurations,id',
            'width' => 'required|numeric|min:0.01',
            'height' => 'required|numeric|min:0.01',
            'quantity' => 'required|integer|min:1',
            'price_per_sqm' => 'required|numeric|min:0',
            'setup_cost' => 'nullable|numeric|min:0',
            'finishing_cost' => 'nullable|numeric|min:0',
            'delivery_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $businessId = $request->user()->current_organization_id;
        $material = PrintMaterial::findOrFail($validated['print_material_id']);
        $inkConfig = InkConfiguration::findOrFail($validated['ink_configuration_id']);

        // Calculate costs
        $area = $validated['width'] * $validated['height'] * $validated['quantity'];
        $costs = $material->calculateCost($area, $inkConfig);

        // Find applicable pricing rule
        $pricingRule = $material->getApplicablePricingRule($area);

        $job = PrintJob::create([
            'organization_id' => $businessId,
            'customer_id' => $validated['customer_id'],
            'print_material_id' => $validated['print_material_id'],
            'ink_configuration_id' => $validated['ink_configuration_id'],
            'pricing_rule_id' => $pricingRule?->id,
            'width' => $validated['width'],
            'height' => $validated['height'],
            'quantity' => $validated['quantity'],
            'material_unit_cost' => $costs['material_unit_cost'],
            'ink_unit_cost' => $costs['ink_unit_cost'],
            'off_cut_cost' => $costs['off_cut_cost'],
            'price_per_sqm' => $validated['price_per_sqm'],
            'setup_cost' => $validated['setup_cost'] ?? 0,
            'finishing_cost' => $validated['finishing_cost'] ?? 0,
            'delivery_cost' => $validated['delivery_cost'] ?? 0,
            'other_costs' => $validated['other_costs'] ?? 0,
            'notes' => $validated['notes'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('print-shop.jobs.show', $job)
            ->with('success', 'Print job created successfully.');
    }

    public function show(Request $request, PrintJob $job)
    {
        // Ensure job belongs to user's organization
        $organizationId = $request->user()->current_organization_id;
        if ($job->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this print job.');
        }

        $job->load(['customer', 'printMaterial', 'inkConfiguration', 'pricingRule', 'createdBy']);

        return Inertia::render('PrintShop/Jobs/Show', [
            'job' => $job,
            'statusOptions' => PrintJob::getStatusOptions(),
        ]);
    }

    public function edit(Request $request, PrintJob $job)
    {
        // Ensure job belongs to user's organization
        $businessId = $request->user()->current_organization_id;
        if ($job->organization_id !== $businessId) {
            abort(403, 'Unauthorized access to this print job.');
        }

        $materials = PrintMaterial::where('organization_id', $businessId)
            ->where('is_active', true)
            ->with('inkConfigurations')
            ->get();

        $customers = Customer::where('organization_id', $businessId)->orderBy('name')->get();

        $job->load(['printMaterial.inkConfigurations']);

        return Inertia::render('PrintShop/Jobs/Edit', [
            'job' => $job,
            'materials' => $materials,
            'customers' => $customers,
            'statusOptions' => PrintJob::getStatusOptions(),
        ]);
    }

    public function update(Request $request, PrintJob $job)
    {
        // Ensure job belongs to user's organization
        $organizationId = $request->user()->current_organization_id;
        if ($job->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this print job.');
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'print_material_id' => 'required|exists:print_materials,id',
            'ink_configuration_id' => 'required|exists:ink_configurations,id',
            'width' => 'required|numeric|min:0.01',
            'height' => 'required|numeric|min:0.01',
            'quantity' => 'required|integer|min:1',
            'price_per_sqm' => 'required|numeric|min:0',
            'setup_cost' => 'nullable|numeric|min:0',
            'finishing_cost' => 'nullable|numeric|min:0',
            'delivery_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $material = PrintMaterial::findOrFail($validated['print_material_id']);
        $inkConfig = InkConfiguration::findOrFail($validated['ink_configuration_id']);

        // Recalculate costs if material or ink changed
        $area = $validated['width'] * $validated['height'] * $validated['quantity'];
        $costs = $material->calculateCost($area, $inkConfig);
        $pricingRule = $material->getApplicablePricingRule($area);

        $job->update([
            ...$validated,
            'pricing_rule_id' => $pricingRule?->id,
            'material_unit_cost' => $costs['material_unit_cost'],
            'ink_unit_cost' => $costs['ink_unit_cost'],
            'off_cut_cost' => $costs['off_cut_cost'],
            'setup_cost' => $validated['setup_cost'] ?? 0,
            'finishing_cost' => $validated['finishing_cost'] ?? 0,
            'delivery_cost' => $validated['delivery_cost'] ?? 0,
            'other_costs' => $validated['other_costs'] ?? 0,
        ]);

        return redirect()->route('print-shop.jobs.show', $job)
            ->with('success', 'Print job updated successfully.');
    }

    public function destroy(Request $request, PrintJob $job)
    {
        // Ensure job belongs to user's organization
        $organizationId = $request->user()->current_organization_id;
        if ($job->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this print job.');
        }

        $job->delete();

        return redirect()->route('print-shop.jobs.index')
            ->with('success', 'Print job deleted successfully.');
    }

    // Quick calculate without saving
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'print_material_id' => 'required|exists:print_materials,id',
            'ink_configuration_id' => 'required|exists:ink_configurations,id',
            'width' => 'required|numeric|min:0.01',
            'height' => 'required|numeric|min:0.01',
            'quantity' => 'required|integer|min:1',
            'price_per_sqm' => 'nullable|numeric|min:0',
        ]);

        $material = PrintMaterial::findOrFail($validated['print_material_id']);
        $inkConfig = InkConfiguration::findOrFail($validated['ink_configuration_id']);

        $area = $validated['width'] * $validated['height'] * $validated['quantity'];
        $costs = $material->calculateCost($area, $inkConfig);

        // Get applicable pricing rule
        $pricingRule = $material->getApplicablePricingRule($area);
        
        // Calculate suggested price
        $suggestedPricePerSqm = $pricingRule 
            ? $pricingRule->calculatePricePerSqm($costs['total_unit_cost'])
            : $costs['total_unit_cost'] * 2; // Default 100% markup

        $pricePerSqm = $validated['price_per_sqm'] ?? $suggestedPricePerSqm;
        $totalPrice = $pricePerSqm * $area;
        $marginPerSqm = $pricePerSqm - $costs['total_unit_cost'];
        $totalMargin = $marginPerSqm * $area;
        $marginPercentage = $pricePerSqm > 0 ? ($marginPerSqm / $pricePerSqm) * 100 : 0;

        return response()->json([
            'dimensions' => [
                'width' => $validated['width'],
                'height' => $validated['height'],
                'quantity' => $validated['quantity'],
                'total_area' => round($area, 2),
            ],
            'costs' => $costs,
            'pricing' => [
                'suggested_price_per_sqm' => round($suggestedPricePerSqm, 2),
                'price_per_sqm' => round($pricePerSqm, 2),
                'total_price' => round($totalPrice, 2),
                'margin_per_sqm' => round($marginPerSqm, 2),
                'total_margin' => round($totalMargin, 2),
                'margin_percentage' => round($marginPercentage, 2),
            ],
            'pricing_rule' => $pricingRule ? [
                'id' => $pricingRule->id,
                'name' => $pricingRule->rule_name,
            ] : null,
            'material' => [
                'id' => $material->id,
                'name' => $material->name,
            ],
        ]);
    }

    // Compare materials for same dimensions
    public function compareMaterials(Request $request)
    {
        $validated = $request->validate([
            'material_ids' => 'required|array|min:1',
            'material_ids.*' => 'exists:print_materials,id',
            'width' => 'required|numeric|min:0.01',
            'height' => 'required|numeric|min:0.01',
            'quantity' => 'required|integer|min:1',
        ]);

        $area = $validated['width'] * $validated['height'] * $validated['quantity'];
        $comparisons = [];

        foreach ($validated['material_ids'] as $materialId) {
            $material = PrintMaterial::with('inkConfigurations')->find($materialId);
            if (!$material) continue;

            $inkConfig = $material->inkConfigurations()->where('is_default', true)->first()
                ?? $material->inkConfigurations()->first();

            if (!$inkConfig) continue;

            $costs = $material->calculateCost($area, $inkConfig);
            $pricingRule = $material->getApplicablePricingRule($area);
            $suggestedPricePerSqm = $pricingRule 
                ? $pricingRule->calculatePricePerSqm($costs['total_unit_cost'])
                : $costs['total_unit_cost'] * 2;

            $comparisons[] = [
                'material' => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'type' => $material->material_type,
                ],
                'costs' => $costs,
                'suggested_price_per_sqm' => round($suggestedPricePerSqm, 2),
                'total_price' => round($suggestedPricePerSqm * $area, 2),
                'margin' => round(($suggestedPricePerSqm - $costs['total_unit_cost']) * $area, 2),
            ];
        }

        // Sort by total cost (lowest first)
        usort($comparisons, fn($a, $b) => $a['costs']['total_cost'] <=> $b['costs']['total_cost']);

        return response()->json([
            'dimensions' => [
                'width' => $validated['width'],
                'height' => $validated['height'],
                'quantity' => $validated['quantity'],
                'total_area' => round($area, 2),
            ],
            'comparisons' => $comparisons,
        ]);
    }

    // Status updates
    public function approve(Request $request, PrintJob $job)
    {
        // Ensure job belongs to user's organization
        $organizationId = $request->user()->current_organization_id;
        if ($job->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this print job.');
        }
        $job->approve();
        return back()->with('success', 'Job approved successfully.');
    }

    public function complete(Request $request, PrintJob $job)
    {
        // Ensure job belongs to user's organization
        $organizationId = $request->user()->current_organization_id;
        if ($job->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this print job.');
        }
        $job->complete();
        return back()->with('success', 'Job marked as completed.');
    }

    public function updateStatus(Request $request, PrintJob $job)
    {
        // Ensure job belongs to user's organization
        $organizationId = $request->user()->current_organization_id;
        if ($job->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this print job.');
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,quoted,approved,in_progress,completed,cancelled',
        ]);

        $job->update(['status' => $validated['status']]);

        // Update timestamps based on status
        match ($validated['status']) {
            'quoted' => $job->update(['quoted_at' => now()]),
            'approved' => $job->update(['approved_at' => now()]),
            'completed' => $job->update(['completed_at' => now()]),
            default => null,
        };

        return back()->with('success', 'Job status updated.');
    }

    /**
     * Convert print job to quotation
     */
    public function convertToQuotation(Request $request, PrintJob $job)
    {
        // Ensure job belongs to user's organization
        $organizationId = $request->user()->current_organization_id;
        if ($job->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this print job.');
        }

        if ($job->quotation_id) {
            return back()->withErrors(['error' => 'This job already has a quotation.']);
        }

        if (!$job->customer_id) {
            return back()->withErrors(['error' => 'A customer must be assigned to the job before creating a quotation.']);
        }

        DB::beginTransaction();
        try {
            $quotation = Quotation::create([
                'organization_id' => $organizationId,
                'customer_id' => $job->customer_id,
                'print_job_id' => $job->id,
                'created_by' => $request->user()->id,
                'title' => "Print Job: {$job->job_number}",
                'description' => $job->notes ?? "Print job for {$job->printMaterial->name} - {$job->width}m x {$job->height}m x {$job->quantity}",
                'status' => 'draft',
                'issue_date' => now(),
                'valid_until' => now()->addDays(30),
                'subtotal' => $job->total_price,
                'tax_percentage' => 16.00, // Default VAT
                'tax_amount' => $job->total_price * 0.16,
                'total' => $job->grand_total,
                'currency' => 'ZMW',
                'payment_terms' => 'Net 30 days',
                'terms_and_conditions' => "1. Prices valid for 30 days\n2. 50% deposit required to commence work\n3. Balance due on completion",
            ]);

            // Create quotation item from print job
            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'order' => 1,
                'name' => "{$job->printMaterial->name} - {$job->width}m x {$job->height}m x {$job->quantity}",
                'description' => "Print job: {$job->job_number}",
                'quantity' => $job->total_area,
                'unit' => 'sqm',
                'unit_price' => $job->price_per_sqm,
                'total' => $job->total_price,
            ]);

            // Add additional costs as separate items if any
            if ($job->additional_costs > 0) {
                $itemNumber = 2;
                if ($job->setup_cost > 0) {
                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'order' => $itemNumber++,
                        'name' => 'Setup Fee',
                        'quantity' => 1,
                        'unit' => 'pcs',
                        'unit_price' => $job->setup_cost,
                        'total' => $job->setup_cost,
                    ]);
                }
                if ($job->finishing_cost > 0) {
                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'order' => $itemNumber++,
                        'name' => 'Finishing',
                        'quantity' => 1,
                        'unit' => 'pcs',
                        'unit_price' => $job->finishing_cost,
                        'total' => $job->finishing_cost,
                    ]);
                }
                if ($job->delivery_cost > 0) {
                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'order' => $itemNumber++,
                        'name' => 'Delivery',
                        'quantity' => 1,
                        'unit' => 'pcs',
                        'unit_price' => $job->delivery_cost,
                        'total' => $job->delivery_cost,
                    ]);
                }
                if ($job->other_costs > 0) {
                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'order' => $itemNumber++,
                        'name' => 'Other Costs',
                        'quantity' => 1,
                        'unit' => 'pcs',
                        'unit_price' => $job->other_costs,
                        'total' => $job->other_costs,
                    ]);
                }
            }

            // Link quotation to job and update status
            $job->update([
                'quotation_id' => $quotation->id,
                'status' => 'quoted',
                'quoted_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('quotations.show', $quotation)
                ->with('success', 'Quotation created successfully from print job.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create quotation: ' . $e->getMessage()]);
        }
    }

    /**
     * Convert print job to invoice (directly, skipping quotation)
     */
    public function convertToInvoice(Request $request, PrintJob $job)
    {
        // Ensure job belongs to user's organization
        $organizationId = $request->user()->current_organization_id;
        if ($job->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this print job.');
        }

        if ($job->invoice_id) {
            return back()->withErrors(['error' => 'This job already has an invoice.']);
        }

        if (!$job->customer_id) {
            return back()->withErrors(['error' => 'A customer must be assigned to the job before creating an invoice.']);
        }

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'customer_id' => $job->customer_id,
                'print_job_id' => $job->id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'subtotal' => $job->total_price,
                'tax_amount' => $job->total_price * 0.16, // Default VAT
                'total_amount' => $job->grand_total,
                'status' => 'sent',
                'notes' => "Invoice for print job: {$job->job_number}",
            ]);

            // Create invoice item from print job
            InvoiceItem::create([
                'id' => Str::uuid(),
                'invoice_id' => $invoice->id,
                'name' => "{$job->printMaterial->name} - {$job->width}m x {$job->height}m x {$job->quantity}",
                'description' => "Print job: {$job->job_number}",
                'quantity' => $job->total_area,
                'unit_price' => $job->price_per_sqm,
                'total' => $job->total_price,
                'display_order' => 1,
            ]);

            // Add additional costs as separate items if any
            $displayOrder = 2;
            if ($job->setup_cost > 0) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => 'Setup Fee',
                    'quantity' => 1,
                    'unit_price' => $job->setup_cost,
                    'total' => $job->setup_cost,
                    'display_order' => $displayOrder++,
                ]);
            }
            if ($job->finishing_cost > 0) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => 'Finishing',
                    'quantity' => 1,
                    'unit_price' => $job->finishing_cost,
                    'total' => $job->finishing_cost,
                    'display_order' => $displayOrder++,
                ]);
            }
            if ($job->delivery_cost > 0) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => 'Delivery',
                    'quantity' => 1,
                    'unit_price' => $job->delivery_cost,
                    'total' => $job->delivery_cost,
                    'display_order' => $displayOrder++,
                ]);
            }
            if ($job->other_costs > 0) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => 'Other Costs',
                    'quantity' => 1,
                    'unit_price' => $job->other_costs,
                    'total' => $job->other_costs,
                    'display_order' => $displayOrder++,
                ]);
            }

            // Link invoice to job
            $job->update([
                'invoice_id' => $invoice->id,
            ]);

            DB::commit();

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice created successfully from print job.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create invoice: ' . $e->getMessage()]);
        }
    }
}

