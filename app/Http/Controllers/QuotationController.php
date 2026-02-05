<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Customer;
use App\Models\Prospect;
use App\Models\GoodsAndService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class QuotationController extends Controller
{
    /**
     * Get current organization ID
     */
    protected function getOrganizationId(): ?string
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $currentOrgId = session('current_organization_id');
        if ($currentOrgId) {
            $org = $user->organizations()->where('organizations.id', $currentOrgId)->first();
            if ($org) {
                return $org->id;
            }
        }
        
        return $user->organizations()->first()?->id;
    }

    /**
     * Display a listing of quotations
     */
    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }
        
        $quotations = Quotation::where('organization_id', $organizationId)
            ->with(['customer', 'prospect', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get data for modal
        $customers = Customer::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $prospects = Prospect::where('organization_id', $organizationId)
            ->whereNotIn('stage', ['won', 'lost'])
            ->orderBy('name')
            ->get(['id', 'name', 'company_name']);

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotations,
            'customers' => $customers,
            'prospects' => $prospects,
        ]);
    }

    /**
     * Show the form for creating a new quotation
     */
    public function create()
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }
        
        $customers = Customer::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $prospects = Prospect::where('organization_id', $organizationId)
            ->whereNotIn('stage', ['won', 'lost'])
            ->orderBy('name')
            ->get(['id', 'name', 'company_name']);

        return Inertia::render('Quotes/Create', [
            'customers' => $customers,
            'prospects' => $prospects,
        ]);
    }

    /**
     * Store a newly created quotation
     */
    public function store(Request $request)
    {
        Log::info('Quotation store called', [
            'user_id' => Auth::id(),
            'has_items' => $request->has('items'),
            'items_type' => gettype($request->items),
            'items_count' => is_array($request->items) ? count($request->items) : 0,
            'items_raw' => $request->items,
            'all_data' => $request->all(),
        ]);

        $validated = $request->validate([
            'customer_id' => 'nullable|uuid|exists:customers,id|required_without:prospect_id',
            'prospect_id' => 'nullable|uuid|exists:prospects,id|required_without:customer_id',
            'quote_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:quote_date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.goods_service_id' => 'nullable|uuid|exists:goods_and_services,id',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
        ]);

        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'You must belong to an organization.'])->withInput();
        }

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $taxAmount = $validated['tax_amount'] ?? 0;
            $discountAmount = $validated['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Create quotation
            $quotation = Quotation::create([
                'organization_id' => $organizationId,
                'customer_id' => $validated['customer_id'] ?? null,
                'prospect_id' => $validated['prospect_id'] ?? null,
                'created_by' => Auth::id(),
                'title' => 'Quotation',
                'issue_date' => $validated['quote_date'],
                'valid_until' => $validated['expiry_date'] ?? now()->addDays(30),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $totalAmount,
                'status' => 'draft',
                'terms_and_conditions' => $validated['terms'] ?? null,
                'internal_notes' => $validated['notes'] ?? null,
                'currency' => Auth::user()->organization?->currency ?? 'ZMW',
            ]);

            // Create quotation items
            foreach ($validated['items'] as $index => $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['goods_service_id'] ?? null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'order' => $index,
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            Log::info('Quotation created', [
                'quotation_id' => $quotation->id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('quotations.show', $quotation)
                ->with('success', 'Quotation created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quotation creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors(['error' => 'Failed to create quotation: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified quotation
     */
    public function show(Quotation $quotation)
    {
        $quotation->load(['items.product', 'customer', 'prospect']);
        
        return Inertia::render('Quotes/Show', [
            'quote' => $quotation,
        ]);
    }

    /**
     * Show the form for editing the specified quotation
     */
    public function edit(Quotation $quotation)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }

        if ($quotation->converted_to_invoice_id) {
            return back()->withErrors(['error' => 'Cannot edit quotation that has been converted to invoice.']);
        }

        $quotation->load(['items', 'customer', 'prospect']);
        
        $customers = Customer::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Quotes/Edit', [
            'quote' => $quotation,
            'customers' => $customers,
        ]);
    }

    /**
     * Update the specified quotation
     */
    public function update(Request $request, Quotation $quotation)
    {
        if ($quotation->converted_to_invoice_id) {
            return back()->withErrors(['error' => 'Cannot edit quotation that has been converted to invoice.']);
        }

        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'quote_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:quote_date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.goods_service_id' => 'nullable|uuid|exists:goods_and_services,id',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $taxAmount = $validated['tax_amount'] ?? 0;
            $discountAmount = $validated['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Update quotation
            $quotation->update([
                'customer_id' => $validated['customer_id'],
                'issue_date' => $validated['quote_date'],
                'valid_until' => $validated['expiry_date'] ?? now()->addDays(30),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $totalAmount,
                'terms_and_conditions' => $validated['terms'] ?? null,
                'internal_notes' => $validated['notes'] ?? null,
            ]);

            // Delete existing items and create new ones
            $quotation->items()->delete();

            foreach ($validated['items'] as $index => $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['goods_service_id'] ?? null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'order' => $index,
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return redirect()->route('quotations.show', $quotation)
                ->with('success', 'Quotation updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quotation update failed', [
                'error' => $e->getMessage(),
                'quotation_id' => $quotation->id,
            ]);
            return back()->withErrors(['error' => 'Failed to update quotation: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified quotation
     */
    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return redirect()->route('quotations.index')
            ->with('success', 'Quotation deleted successfully.');
    }

    /**
     * Convert quotation to invoice
     */
    public function convertToInvoice(Request $request, Quotation $quotation)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }

        if ($quotation->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this quotation.');
        }

        if ($quotation->converted_to_invoice_id) {
            return redirect()->route('quotations.show', $quotation)
                ->with('info', 'This quotation has already been converted to an invoice.');
        }

        try {
            // If quotation has prospect but no customer, convert prospect to customer first
            if (!$quotation->customer_id && $quotation->prospect_id) {
                $prospect = $quotation->prospect;
                if ($prospect) {
                    $customer = $prospect->convertToCustomer([]);
                    $quotation->update(['customer_id' => $customer->id]);
                    $quotation->refresh();
                }
            }

            if (!$quotation->customer_id) {
                return back()->withErrors([
                    'error' => 'This quotation must have a customer before converting to an invoice. Please edit the quotation to assign a customer.',
                ]);
            }

            $invoice = $quotation->convertToInvoice();

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Quotation converted to invoice successfully.');
        } catch (\Exception $e) {
            Log::error('Quotation convert to invoice failed', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to convert: ' . $e->getMessage()]);
        }
    }

    /**
     * Update quotation status
     */
    public function updateStatus(Request $request, Quotation $quotation)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }

        if ($quotation->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this quotation.');
        }

        if ($quotation->converted_to_invoice_id) {
            return back()->withErrors(['error' => 'Cannot update status of a converted quotation.']);
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,approved,sent,complete,viewed,accepted,rejected,expired',
        ]);

        $quotation->update(['status' => $validated['status']]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $quotation->status]);
        }

        return redirect()->back()->with('success', 'Quotation status updated.');
    }

    /**
     * Download quotation as PDF
     */
    public function downloadPdf(Quotation $quotation)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to download quotations.');
        }

        // Ensure the quotation belongs to the user's organization
        if ($quotation->organization_id !== $organizationId) {
            abort(403, 'Unauthorized access to this quotation.');
        }

        $quotation->load(['items.product', 'customer', 'prospect', 'organization']);

        $organization = $quotation->organization;
        $logoBase64 = null;
        
        if ($organization->logo && \Storage::disk('public')->exists($organization->logo)) {
            try {
                $logoPath = \Storage::disk('public')->path($organization->logo);
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $mimeType = mime_content_type($logoPath) ?: 'image/png';
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to load organization logo for quotation PDF', [
                    'organization_id' => $organization->id,
                    'logo' => $organization->logo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $pdfService = new \App\Services\PDF\PdfService();
        $filename = 'Quotation-' . $quotation->quotation_number . '.pdf';

        return $pdfService->download('pdf.quotation', [
            'quotation' => $quotation,
            'organization' => $organization,
            'logoUrl' => $logoBase64,
        ], $filename);
    }

    /**
     * Search products for quotation items
     */
    public function searchProducts(Request $request)
    {
        try {
            $organizationId = $this->getOrganizationId();
            if (!$organizationId) {
                Log::warning('Product search: No organization ID', ['user_id' => Auth::id()]);
                return response()->json([], 200);
            }

            $query = $request->input('q', '');
            
            if (strlen($query) < 1) {
                return response()->json([], 200);
            }

            $products = GoodsAndService::where('organization_id', $organizationId)
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('sku', 'like', "%{$query}%")
                      ->orWhere('barcode', 'like', "%{$query}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'description', 'selling_price', 'sku']);

            Log::debug('Product search results', [
                'query' => $query,
                'organization_id' => $organizationId,
                'count' => $products->count(),
            ]);

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Product search error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([], 200);
        }
    }
}
