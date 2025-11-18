<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\GoodsAndService;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::where('organization_id', Auth::user()->organization_id)
            ->with('customer')
            ->orderBy('invoice_date', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(20);

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(Request $request)
    {
        $customers = Customer::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        $products = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $quoteId = $request->query('quote_id');
        $quote = null;
        if ($quoteId) {
            $quote = Quote::where('organization_id', Auth::user()->organization_id)
                ->with(['items', 'customer'])
                ->find($quoteId);
        }

        // Get organization bank details
        $organization = \App\Models\Organization::find(Auth::user()->organization_id);
        $bankDetails = $organization->settings['bank_details'] ?? null;

        return Inertia::render('Invoices/Create', [
            'customers' => $customers,
            'products' => $products,
            'quote' => $quote,
            'bankDetails' => $bankDetails,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.goods_service_id' => 'nullable|uuid|exists:goods_and_services,id',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'is_recurring' => 'boolean',
            'recurrence_frequency' => 'nullable|in:weekly,monthly,quarterly,annually',
            'recurrence_day' => 'nullable|integer|min:1|max:31',
            'recurrence_end_date' => 'nullable|date',
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

            // Calculate next invoice date if recurring
            $nextInvoiceDate = null;
            if ($validated['is_recurring'] ?? false) {
                $nextInvoiceDate = $this->calculateNextInvoiceDate(
                    $validated['invoice_date'],
                    $validated['recurrence_frequency'],
                    $validated['recurrence_day'] ?? null
                );
            }

            // Create invoice
            $invoice = Invoice::create([
                'id' => (string) Str::uuid(),
                'organization_id' => Auth::user()->organization_id,
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? now()->addDays(30)->toDateString(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'payment_details' => $validated['payment_details'] ?? null,
                'quote_id' => $request->input('quote_id'),
                'is_recurring' => $validated['is_recurring'] ?? false,
                'recurrence_frequency' => $validated['recurrence_frequency'] ?? null,
                'recurrence_day' => $validated['recurrence_day'] ?? null,
                'next_invoice_date' => $nextInvoiceDate,
                'recurrence_end_date' => $validated['recurrence_end_date'] ?? null,
                'parent_invoice_id' => $request->input('parent_invoice_id'),
            ]);

            // Create invoice items
            foreach ($validated['items'] as $index => $item) {
                InvoiceItem::create([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'goods_service_id' => $item['goods_service_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'display_order' => $index,
                ]);
            }

            DB::commit();

            return redirect()->route('invoices.show', $invoice->id)->with('message', 'Invoice created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create invoice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors(['error' => 'Failed to create invoice: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $invoice = Invoice::where('organization_id', Auth::user()->organization_id)
            ->with(['customer', 'items.goodsService', 'payments.payment', 'attachments.uploadedBy'])
            ->findOrFail($id);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function downloadPdf($id)
    {
        $invoice = Invoice::where('organization_id', Auth::user()->organization_id)
            ->with(['customer', 'items.goodsService', 'organization'])
            ->findOrFail($id);

        $organization = $invoice->organization;
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

        // Get bank details - use invoice-specific if available, otherwise fall back to organization defaults
        $bankDetails = null;
        $organizationBankDetails = $organization->settings['bank_details'] ?? null;
        
        if ($invoice->payment_details && $organizationBankDetails) {
            // Build bank details based on invoice payment_details selections
            $bankDetails = [];
            $paymentDetails = $invoice->payment_details;
            
            if (!empty($paymentDetails['show_bank_name']) && !empty($organizationBankDetails['bank_name'])) {
                $bankDetails['bank_name'] = $organizationBankDetails['bank_name'];
            }
            if (!empty($paymentDetails['show_account_name']) && !empty($organizationBankDetails['account_name'])) {
                $bankDetails['account_name'] = $organizationBankDetails['account_name'];
            }
            if (!empty($paymentDetails['show_account_number']) && !empty($organizationBankDetails['account_number'])) {
                $bankDetails['account_number'] = $organizationBankDetails['account_number'];
            }
            if (!empty($paymentDetails['show_branch']) && !empty($organizationBankDetails['branch'])) {
                $bankDetails['branch'] = $organizationBankDetails['branch'];
            }
            if (!empty($paymentDetails['show_swift_code']) && !empty($organizationBankDetails['swift_code'])) {
                $bankDetails['swift_code'] = $organizationBankDetails['swift_code'];
            }
            if (!empty($paymentDetails['show_mobile_money']) && !empty($organizationBankDetails['mobile_money'])) {
                $bankDetails['mobile_money'] = $organizationBankDetails['mobile_money'];
            }
            if (!empty($paymentDetails['show_payment_options']) && !empty($organizationBankDetails['payment_options'])) {
                $bankDetails['payment_options'] = $organizationBankDetails['payment_options'];
            }
            if (!empty($paymentDetails['show_notes']) && !empty($organizationBankDetails['notes'])) {
                $bankDetails['notes'] = $organizationBankDetails['notes'];
            }
        } elseif ($organizationBankDetails) {
            // Fall back to showing all organization bank details if no invoice-specific selection
            $bankDetails = $organizationBankDetails;
        }

        $pdfService = new \App\Services\PDF\PdfService();
        $filename = 'Invoice-' . $invoice->invoice_number . '.pdf';

        return $pdfService->download('pdf.invoice', [
            'invoice' => $invoice,
            'organization' => $organization,
            'logoUrl' => $logoBase64,
            'bankDetails' => $bankDetails,
        ], $filename);
    }

    public function edit($id)
    {
        $invoice = Invoice::where('organization_id', Auth::user()->organization_id)
            ->with(['items', 'customer'])
            ->findOrFail($id);

        $customers = Customer::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        $products = GoodsAndService::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get organization bank details
        $organization = \App\Models\Organization::find(Auth::user()->organization_id);
        $bankDetails = $organization->settings['bank_details'] ?? null;

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'products' => $products,
            'bankDetails' => $bankDetails,
        ]);
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        // Prevent editing if invoice is paid or has payments
        if ($invoice->status === 'paid' || ($invoice->paid_amount && $invoice->paid_amount > 0)) {
            return back()->withErrors(['error' => 'Cannot edit an invoice that has been paid or has payments']);
        }

        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.goods_service_id' => 'nullable|uuid|exists:goods_and_services,id',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'payment_details' => 'nullable|array',
            'payment_details.show_bank_name' => 'nullable|boolean',
            'payment_details.show_account_name' => 'nullable|boolean',
            'payment_details.show_account_number' => 'nullable|boolean',
            'payment_details.show_branch' => 'nullable|boolean',
            'payment_details.show_swift_code' => 'nullable|boolean',
            'payment_details.show_mobile_money' => 'nullable|boolean',
            'payment_details.show_payment_options' => 'nullable|boolean',
            'payment_details.show_notes' => 'nullable|boolean',
            'is_recurring' => 'boolean',
            'recurrence_frequency' => 'nullable|in:weekly,monthly,quarterly,annually',
            'recurrence_day' => 'nullable|integer|min:1|max:31',
            'recurrence_end_date' => 'nullable|date',
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

            // Calculate next invoice date if recurring
            $nextInvoiceDate = null;
            if ($validated['is_recurring'] ?? false) {
                $nextInvoiceDate = $this->calculateNextInvoiceDate(
                    $validated['invoice_date'],
                    $validated['recurrence_frequency'],
                    $validated['recurrence_day'] ?? null
                );
            }

            // Update invoice
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? now()->addDays(30)->toDateString(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'payment_details' => $validated['payment_details'] ?? null,
                'is_recurring' => $validated['is_recurring'] ?? false,
                'recurrence_frequency' => $validated['recurrence_frequency'] ?? null,
                'recurrence_day' => $validated['recurrence_day'] ?? null,
                'next_invoice_date' => $nextInvoiceDate,
                'recurrence_end_date' => $validated['recurrence_end_date'] ?? null,
            ]);

            // Delete old items
            $invoice->items()->delete();

            // Create new items
            foreach ($validated['items'] as $index => $item) {
                InvoiceItem::create([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'goods_service_id' => $item['goods_service_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'display_order' => $index,
                ]);
            }

            DB::commit();

            return redirect()->route('invoices.show', $invoice->id)->with('message', 'Invoice updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update invoice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors(['error' => 'Failed to update invoice: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $invoice = Invoice::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        // Prevent deletion if invoice is paid or has payments
        if ($invoice->status === 'paid' || ($invoice->paid_amount && $invoice->paid_amount > 0)) {
            return back()->withErrors(['error' => 'Cannot delete an invoice that has been paid or has payments']);
        }

        DB::beginTransaction();
        try {
            // Delete invoice items
            $invoice->items()->delete();
            
            // Delete invoice
            $invoice->delete();

            DB::commit();

            return redirect()->route('invoices.index')->with('message', 'Invoice deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete invoice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors(['error' => 'Failed to delete invoice: ' . $e->getMessage()]);
        }
    }

    public function send($id)
    {
        $invoice = Invoice::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        // TODO: Implement email sending
        $invoice->update(['status' => 'sent']);

        return back()->with('message', 'Invoice sent successfully');
    }

    protected function calculateNextInvoiceDate($startDate, $frequency, $day): ?string
    {
        $date = \Carbon\Carbon::parse($startDate);
        
        switch ($frequency) {
            case 'weekly':
                return $date->addWeek()->toDateString();
            case 'monthly':
                if ($day) {
                    return $date->addMonth()->day($day)->toDateString();
                }
                return $date->addMonth()->toDateString();
            case 'quarterly':
                return $date->addMonths(3)->toDateString();
            case 'annually':
                return $date->addYear()->toDateString();
            default:
                return null;
        }
    }
}
