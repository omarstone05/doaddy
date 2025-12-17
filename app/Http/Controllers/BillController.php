<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        $bills = Bill::where('organization_id', $organizationId)
            ->with('vendor')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Bills/Index', [
            'bills' => $bills,
        ]);
    }

    public function create()
    {
        $organizationId = Auth::user()->organization_id;
        
        $vendors = \App\Models\Vendor::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Bills/Create', [
            'vendors' => $vendors,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bill_date' => 'required|date',
            'due_date' => 'required|date',
            'category' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        $validated['created_by'] = Auth::id();
        $validated['status'] = $validated['status'] ?? 'pending_approval';
        $validated['payment_status'] = 'unpaid';

        $bill = Bill::create($validated);
        $bill->calculateTotals();

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Bill created successfully.');
    }

    public function show(Bill $bill)
    {
        $bill->load(['items', 'vendor', 'payments']);
        
        return Inertia::render('Bills/Show', [
            'bill' => $bill,
        ]);
    }

    public function edit(Bill $bill)
    {
        $bill->load(['items', 'vendor']);
        
        return Inertia::render('Bills/Edit', [
            'bill' => $bill,
        ]);
    }

    public function update(Request $request, Bill $bill)
    {
        $validated = $request->validate([
            'bill_date' => 'required|date',
            'due_date' => 'required|date',
            'category' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $bill->update($validated);
        $bill->calculateTotals();

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Bill updated successfully.');
    }

    public function destroy(Bill $bill)
    {
        $bill->delete();

        return redirect()->route('bills.index')
            ->with('success', 'Bill deleted successfully.');
    }

    public function approve(Bill $bill)
    {
        $bill->approve(Auth::id());

        return redirect()->back()
            ->with('success', 'Bill approved successfully.');
    }

    public function recordPayment(Request $request, Bill $bill)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $bill->recordPayment($validated['amount'], $validated);

        return redirect()->back()
            ->with('success', 'Payment recorded successfully.');
    }

    public function cancel(Bill $bill)
    {
        $bill->update(['status' => 'cancelled']);

        return redirect()->back()
            ->with('success', 'Bill cancelled successfully.');
    }
}

