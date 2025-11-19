<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\MoneyAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaymentController extends Controller
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
            abort(403, 'You must belong to an organization to access payments.');
        }

        $payments = Payment::where('organization_id', $organizationId)
            ->with(['customer', 'allocations.invoice'])
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
        ]);
    }

    public function create(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to create payments.');
        }

        $customerId = $request->query('customer_id');
        $invoiceId = $request->query('invoice_id');
        
        $customers = Customer::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get();

        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get customer's unpaid invoices if customer selected
        $invoices = [];
        $selectedInvoice = null;
        $prefillAllocation = null;
        
        if ($invoiceId) {
            // If invoice_id is provided, fetch that specific invoice
            $selectedInvoice = Invoice::where('organization_id', $organizationId)
                ->with('customer')
                ->find($invoiceId);
            
            if ($selectedInvoice) {
                // Set customer_id from invoice if not already set
                if (!$customerId) {
                    $customerId = $selectedInvoice->customer_id;
                }
                
                // Calculate outstanding amount
                $outstandingAmount = $selectedInvoice->total_amount - ($selectedInvoice->paid_amount ?? 0);
                
                // Pre-fill allocation
                if ($outstandingAmount > 0) {
                    $prefillAllocation = [
                        'invoice_id' => $selectedInvoice->id,
                        'invoice_number' => $selectedInvoice->invoice_number,
                        'amount' => $outstandingAmount,
                    ];
                }
            }
        }
        
        if ($customerId) {
            $invoices = Invoice::where('organization_id', $organizationId)
                ->where('customer_id', $customerId)
                ->where(function ($q) {
                    $q->where('status', '!=', 'paid')
                      ->orWhereRaw('total_amount > paid_amount');
                })
                ->orderBy('invoice_date')
                ->get();
        }

        return Inertia::render('Payments/Create', [
            'customers' => $customers,
            'accounts' => $accounts,
            'invoices' => $invoices,
            'selectedCustomerId' => $customerId,
            'prefillAllocation' => $prefillAllocation,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,mobile_money,card,bank_transfer,cheque,other',
            'payment_reference' => 'nullable|string|max:255',
            'money_account_id' => 'nullable|uuid|exists:money_accounts,id',
            'notes' => 'nullable|string',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => 'required|uuid|exists:invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'invoice_id' => 'nullable|uuid|exists:invoices,id', // For auto-allocation from invoice page
        ]);

        DB::beginTransaction();
        try {
            $organizationId = $this->getOrganizationId();
            if (!$organizationId) {
                throw new \Exception('You must belong to an organization to create payments.');
            }

            // Create payment
            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'customer_id' => $validated['customer_id'],
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'money_account_id' => $validated['money_account_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create allocations if provided
            if (!empty($validated['allocations'])) {
                foreach ($validated['allocations'] as $allocation) {
                    PaymentAllocation::create([
                        'id' => (string) Str::uuid(),
                        'payment_id' => $payment->id,
                        'invoice_id' => $allocation['invoice_id'],
                        'amount' => $allocation['amount'],
                    ]);
                }
            } elseif ($request->has('invoice_id')) {
                // If invoice_id is provided but no allocations, automatically allocate full payment amount to that invoice
                $invoiceId = $request->input('invoice_id');
                $invoice = Invoice::where('organization_id', $organizationId)
                    ->find($invoiceId);
                
                if ($invoice) {
                    // Allocate the full payment amount to the invoice (or outstanding amount if less)
                    $outstandingAmount = $invoice->total_amount - ($invoice->paid_amount ?? 0);
                    $allocationAmount = min($validated['amount'], $outstandingAmount);
                    
                    if ($allocationAmount > 0) {
                        PaymentAllocation::create([
                            'id' => (string) Str::uuid(),
                            'payment_id' => $payment->id,
                            'invoice_id' => $invoiceId,
                            'amount' => $allocationAmount,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('payments.show', $payment->id)->with('message', 'Payment recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to record payment: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to view payments.');
        }

        $payment = Payment::where('organization_id', $organizationId)
            ->with(['customer', 'allocations.invoice', 'receipts'])
            ->findOrFail($id);

        return Inertia::render('Payments/Show', [
            'payment' => $payment,
        ]);
    }

    public function showAllocate($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to allocate payments.');
        }

        $payment = Payment::where('organization_id', $organizationId)
            ->with(['customer', 'allocations.invoice'])
            ->findOrFail($id);

        // Calculate unallocated amount
        $currentAllocated = $payment->allocations->sum('amount');
        $unallocatedAmount = $payment->amount - $currentAllocated;

        // Get customer's unpaid invoices
        $invoices = Invoice::where('organization_id', $organizationId)
            ->where('customer_id', $payment->customer_id)
            ->where(function ($q) {
                $q->where('status', '!=', 'paid')
                  ->orWhereRaw('total_amount > COALESCE(paid_amount, 0)');
            })
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(function ($invoice) {
                $outstanding = $invoice->total_amount - ($invoice->paid_amount ?? 0);
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount ?? 0,
                    'outstanding_amount' => $outstanding,
                ];
            });

        return Inertia::render('Payments/Allocate', [
            'payment' => $payment,
            'invoices' => $invoices,
            'unallocatedAmount' => $unallocatedAmount,
        ]);
    }

    public function allocate(Request $request, $id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to allocate payments.');
        }
        
        $payment = Payment::where('organization_id', $organizationId)
            ->with('allocations') // Load allocations relationship for unallocated_amount calculation
            ->findOrFail($id);

        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.invoice_id' => 'required|uuid',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        // Verify all invoices belong to the same organization
        $invoiceIds = array_column($validated['allocations'], 'invoice_id');
        $invoices = Invoice::where('organization_id', $organizationId)
            ->whereIn('id', $invoiceIds)
            ->get();
        
        if ($invoices->count() !== count($invoiceIds)) {
            return back()->withErrors(['error' => 'One or more invoices do not exist or do not belong to your organization']);
        }

        $totalAllocated = array_sum(array_column($validated['allocations'], 'amount'));
        
        // Calculate unallocated amount safely
        $currentAllocated = $payment->allocations->sum('amount');
        $unallocatedAmount = $payment->amount - $currentAllocated;
        
        if ($totalAllocated > $unallocatedAmount) {
            return back()->withErrors(['error' => 'Total allocation exceeds unallocated amount']);
        }

        DB::beginTransaction();
        try {
            foreach ($validated['allocations'] as $allocation) {
                PaymentAllocation::create([
                    'id' => (string) Str::uuid(),
                    'payment_id' => $payment->id,
                    'invoice_id' => $allocation['invoice_id'],
                    'amount' => $allocation['amount'],
                ]);
            }

            DB::commit();

            return back()->with('message', 'Payment allocated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to allocate payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to allocate payment: ' . $e->getMessage()]);
        }
    }
}
