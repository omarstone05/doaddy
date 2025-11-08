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
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::where('organization_id', Auth::user()->organization_id)
            ->with(['customer', 'allocations.invoice'])
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
        ]);
    }

    public function create(Request $request)
    {
        $customerId = $request->query('customer_id');
        
        $customers = Customer::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        $accounts = MoneyAccount::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get customer's unpaid invoices if customer selected
        $invoices = [];
        if ($customerId) {
            $invoices = Invoice::where('organization_id', Auth::user()->organization_id)
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
        ]);

        DB::beginTransaction();
        try {
            // Create payment
            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'organization_id' => Auth::user()->organization_id,
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
            }

            DB::commit();

            return redirect()->route('payments.show', $payment->id)->with('message', 'Payment recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to record payment: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $payment = Payment::where('organization_id', Auth::user()->organization_id)
            ->with(['customer', 'allocations.invoice', 'receipts'])
            ->findOrFail($id);

        return Inertia::render('Payments/Show', [
            'payment' => $payment,
        ]);
    }

    public function allocate(Request $request, $id)
    {
        $payment = Payment::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.invoice_id' => 'required|uuid|exists:invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        $totalAllocated = array_sum(array_column($validated['allocations'], 'amount'));
        
        if ($totalAllocated > $payment->unallocated_amount) {
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
            return back()->withErrors(['error' => 'Failed to allocate payment: ' . $e->getMessage()]);
        }
    }
}
