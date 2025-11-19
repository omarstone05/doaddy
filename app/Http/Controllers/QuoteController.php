<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Customer;
use App\Models\GoodsAndService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QuoteController extends Controller
{
    /**
     * Get current organization ID
     */
    protected function getOrganizationId()
    {
        $user = Auth::user();
        $currentOrgId = session('current_organization_id') ?? $user->current_organization_id;
        
        if ($currentOrgId) {
            return $currentOrgId;
        }
        
        // Fallback to first organization
        return $user->organizations()->first()?->id;
    }

    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to access quotes.');
        }

        $query = Quote::where('organization_id', $organizationId)
            ->with('customer')
            ->orderBy('quote_date', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $quotes = $query->paginate(20);
        
        // Add invoice_id to each quote if it has been converted
        $quotes->getCollection()->transform(function ($quote) {
            $invoice = \App\Models\Invoice::where('quote_id', $quote->id)->first();
            $quote->invoice_id = $invoice ? $invoice->id : null;
            return $quote;
        });

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create()
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to create quotes.');
        }

        $customers = Customer::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get();

        $products = GoodsAndService::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Quotes/Create', [
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'quote_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:quote_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
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

            $organizationId = $this->getOrganizationId();
            if (!$organizationId) {
                throw new \Exception('You must belong to an organization to create quotes.');
            }

            // Create quote
            $quote = Quote::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'customer_id' => $validated['customer_id'],
                'quote_date' => $validated['quote_date'],
                'expiry_date' => $validated['expiry_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
            ]);

            // Create quote items
            foreach ($validated['items'] as $index => $item) {
                QuoteItem::create([
                    'id' => (string) Str::uuid(),
                    'quote_id' => $quote->id,
                    'goods_service_id' => $item['goods_service_id'] ?? null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'display_order' => $index,
                ]);
            }

            DB::commit();

            return redirect()->route('quotes.show', $quote->id)->with('message', 'Quote created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create quote: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to view quotes.');
        }

        $quote = Quote::where('organization_id', $organizationId)
            ->with(['customer', 'items.goodsService', 'attachments.uploadedBy'])
            ->findOrFail($id);
        
        // Check if quote has been converted to an invoice
        $invoice = \App\Models\Invoice::where('quote_id', $quote->id)->first();
        $quote->invoice_id = $invoice ? $invoice->id : null;

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
        ]);
    }

    public function downloadPdf($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to download quotes.');
        }

        $quote = Quote::where('organization_id', $organizationId)
            ->with(['customer', 'items', 'organization'])
            ->findOrFail($id);

        $organization = $quote->organization;
        $logoUrl = null;
        $logoBase64 = null;
        if ($organization->logo && \Storage::disk('public')->exists($organization->logo)) {
            try {
                // Convert logo to base64 for PDF compatibility
                $logoPath = \Storage::disk('public')->path($organization->logo);
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $mimeType = mime_content_type($logoPath) ?: 'image/png';
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to load organization logo for PDF', [
                    'organization_id' => $organization->id,
                    'logo' => $organization->logo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $pdfService = new \App\Services\PDF\PdfService();
        $filename = 'Quote-' . $quote->quote_number . '.pdf';

        return $pdfService->download('pdf.quote', [
            'quote' => $quote,
            'organization' => $organization,
            'logoUrl' => $logoBase64,
        ], $filename);
    }

    public function edit($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to edit quotes.');
        }

        $quote = Quote::where('organization_id', $organizationId)
            ->with(['items', 'customer'])
            ->findOrFail($id);
        
        // Check if quote has been converted to an invoice
        $hasInvoice = \App\Models\Invoice::where('quote_id', $quote->id)->exists();
        if ($hasInvoice) {
            return back()->withErrors(['error' => 'Cannot edit a quote that has been converted to an invoice']);
        }

        $customers = Customer::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get();

        $products = GoodsAndService::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Quotes/Edit', [
            'quote' => $quote,
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to update quotes.');
        }

        $quote = Quote::where('organization_id', $organizationId)
            ->findOrFail($id);
        
        // Prevent editing if quote has been converted to an invoice
        $hasInvoice = \App\Models\Invoice::where('quote_id', $quote->id)->exists();
        if ($hasInvoice) {
            return back()->withErrors(['error' => 'Cannot edit a quote that has been converted to an invoice']);
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

            // Update quote
            $quote->update([
                'customer_id' => $validated['customer_id'],
                'quote_date' => $validated['quote_date'],
                'expiry_date' => $validated['expiry_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
            ]);

            // Delete old items
            $quote->items()->delete();

            // Create new items
            foreach ($validated['items'] as $index => $item) {
                QuoteItem::create([
                    'id' => (string) Str::uuid(),
                    'quote_id' => $quote->id,
                    'goods_service_id' => $item['goods_service_id'] ?? null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'display_order' => $index,
                ]);
            }

            DB::commit();

            return redirect()->route('quotes.show', $quote->id)->with('message', 'Quote updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update quote: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to delete quotes.');
        }

        $quote = Quote::where('organization_id', $organizationId)
            ->findOrFail($id);

        // Prevent deletion if quote has been converted to an invoice
        $hasInvoice = \App\Models\Invoice::where('quote_id', $quote->id)->exists();
        if ($hasInvoice) {
            return back()->withErrors(['error' => 'Cannot delete a quote that has been converted to an invoice']);
        }
        
        // Also prevent deletion if quote is accepted (should be converted first)
        if ($quote->status === 'accepted') {
            return back()->withErrors(['error' => 'Cannot delete an accepted quote. Convert it to an invoice first.']);
        }

        DB::beginTransaction();
        try {
            // Delete quote items
            $quote->items()->delete();
            
            // Delete quote
            $quote->delete();

            DB::commit();

            return redirect()->route('quotes.index')->with('message', 'Quote deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete quote: ' . $e->getMessage()]);
        }
    }

    public function convert($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to convert quotes.');
        }

        $quote = Quote::where('organization_id', $organizationId)
            ->with(['items', 'customer'])
            ->findOrFail($id);

        if ($quote->status !== 'accepted') {
            return back()->withErrors(['error' => 'Only accepted quotes can be converted to invoices']);
        }

        DB::beginTransaction();
        try {
            // Create invoice from quote
            $invoice = \App\Models\Invoice::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $quote->organization_id,
                'customer_id' => $quote->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $quote->subtotal,
                'tax_amount' => $quote->tax_amount,
                'discount_amount' => $quote->discount_amount,
                'total_amount' => $quote->total_amount,
                'status' => 'sent',
                'notes' => $quote->notes,
                'terms' => $quote->terms,
                'quote_id' => $quote->id,
            ]);

            // Copy quote items to invoice items
            foreach ($quote->items as $quoteItem) {
                \App\Models\InvoiceItem::create([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'goods_service_id' => $quoteItem->goods_service_id,
                    'description' => $quoteItem->description,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price,
                    'total' => $quoteItem->total,
                    'display_order' => $quoteItem->display_order,
                ]);
            }

            DB::commit();

            return redirect()->route('invoices.show', $invoice->id)->with('message', 'Quote converted to invoice successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to convert quote: ' . $e->getMessage()]);
        }
    }
}
