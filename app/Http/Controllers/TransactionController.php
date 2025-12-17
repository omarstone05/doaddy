<?php

namespace App\Http\Controllers;

use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Attachment;
use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /**
     * Helper to get the current organization ID
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
        
        if ($user->attributes['organization_id'] ?? null) {
            $org = $user->organizations()->where('organizations.id', $user->attributes['organization_id'])->first();
            if ($org) {
                return $org->id;
            }
        }
        
        return $user->organizations()->first()?->id;
    }

    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $query = MoneyMovement::where('organization_id', $organizationId)
            ->with(['fromAccount', 'toAccount', 'createdBy', 'attachments']);

        // Filter by account
        if ($request->has('account_id') && $request->account_id) {
            $query->where(function($q) use ($request) {
                $q->where('from_account_id', $request->account_id)
                  ->orWhere('to_account_id', $request->account_id);
            });
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('flow_type', $request->type);
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Filter by verification status
        if ($request->has('is_verified')) {
            $isVerified = filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_verified', $isVerified);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('transaction_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('transaction_date', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'transaction_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $transactions = $query->paginate(50);

        // Get all accounts with balances
        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Calculate total balance across all accounts
        $totalBalance = $accounts->sum('current_balance');

        // Get outstanding invoices for category dropdown
        $outstandingInvoices = Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->whereRaw('total_amount > paid_amount')
            ->with('customer')
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(function($invoice) {
                $customerName = $invoice->customer->name ?? 'Unknown';
                $outstandingAmount = $invoice->total_amount - $invoice->paid_amount;
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $customerName,
                    'amount' => $outstandingAmount,
                    'display' => "Invoice #{$invoice->invoice_number} | Payment from {$customerName} | ZMW" . number_format($outstandingAmount, 2) . " Outstanding",
                ];
            });

        // Get unique categories
        $categories = MoneyMovement::where('organization_id', $organizationId)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Money/Transactions/Index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'totalBalance' => $totalBalance,
            'outstandingInvoices' => $outstandingInvoices,
            'categories' => $categories,
            'filters' => $request->only(['account_id', 'type', 'category', 'is_verified', 'from_date', 'to_date', 'search', 'sort_by', 'sort_order']),
        ]);
    }

    public function create()
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $accounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Money/Transactions/Create', [
            'accounts' => $accounts,
            'type' => request()->query('type'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'flow_type' => 'sometimes|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'transaction_date' => 'required|date',
            'from_account_id' => 'required_if:flow_type,expense,transfer|nullable|uuid|exists:money_accounts,id',
            'to_account_id' => 'required_if:flow_type,income,transfer|nullable|uuid|exists:money_accounts,id',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
        ]);

        // Default to expense if not specified (for quick expense entry)
        if (!isset($validated['flow_type'])) {
            $validated['flow_type'] = 'expense';
        }
        if (!isset($validated['currency'])) {
            $validated['currency'] = 'ZMW';
        }
        
        // For quick expense, require from_account_id
        if ($validated['flow_type'] === 'expense' && !isset($validated['from_account_id'])) {
            if ($request->has('from_account_id')) {
                $validated['from_account_id'] = $request->input('from_account_id');
            } else {
                return back()->withErrors(['from_account_id' => 'Please select an account']);
            }
        }

        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        // Validate account belongs to organization
        if ($validated['from_account_id']) {
            MoneyAccount::where('organization_id', $organizationId)
                ->findOrFail($validated['from_account_id']);
        }
        if ($validated['to_account_id']) {
            MoneyAccount::where('organization_id', $organizationId)
                ->findOrFail($validated['to_account_id']);
        }

        DB::beginTransaction();
        try {
        $movement = MoneyMovement::create([
            'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
            'flow_type' => $validated['flow_type'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'transaction_date' => $validated['transaction_date'],
            'from_account_id' => $validated['from_account_id'] ?? null,
            'to_account_id' => $validated['to_account_id'] ?? null,
            'description' => $validated['description'],
            'category' => $validated['category'] ?? null,
            'status' => 'approved',
            'created_by_id' => Auth::id(),
        ]);

            DB::commit();

            return redirect()->route('transactions.index')->with('message', 'Transaction recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to create transaction: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $transaction = MoneyMovement::where('organization_id', $organizationId)
            ->with(['fromAccount', 'toAccount', 'createdBy', 'attachments.uploadedBy'])
            ->findOrFail($id);

        return Inertia::render('Money/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    public function update(Request $request, $id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $transaction = MoneyMovement::where('organization_id', $organizationId)
            ->findOrFail($id);

        $validated = $request->validate([
            'description' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:255',
            'account_id' => 'nullable|uuid|exists:money_accounts,id',
        ]);

        DB::beginTransaction();
        try {
            // Update account if provided
            if (isset($validated['account_id'])) {
                if ($transaction->flow_type === 'income') {
                    $transaction->to_account_id = $validated['account_id'];
                } elseif ($transaction->flow_type === 'expense') {
                    $transaction->from_account_id = $validated['account_id'];
                }
            }

            if (isset($validated['description'])) {
                $transaction->description = $validated['description'];
            }
            if (isset($validated['category'])) {
                $transaction->category = $validated['category'];
            }

            $transaction->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Transaction updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to update transaction'], 500);
        }
    }

    public function uploadReceipt(Request $request, $id, FileManager $fileManager)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $transaction = MoneyMovement::where('organization_id', $organizationId)
            ->findOrFail($id);

        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $uploadedFile = $fileManager->upload($request->file('file'), Auth::user(), 'transaction', $organizationId);
            
            $filePath = $uploadedFile->storage_driver === 'google' 
                ? $uploadedFile->storage_path 
                : Storage::disk('public')->url($uploadedFile->storage_path);
            
            Attachment::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'attachable_type' => MoneyMovement::class,
                'attachable_id' => $transaction->id,
                'name' => $request->file('file')->getClientOriginalName(),
                'file_path' => $uploadedFile->storage_path,
                'file_name' => $uploadedFile->file_name,
                'file_size' => $uploadedFile->file_size,
                'mime_type' => $uploadedFile->mime_type,
                'url' => $filePath,
                'uploaded_by_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Receipt uploaded successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to upload receipt', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to upload receipt'], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return response()->json(['success' => false, 'message' => 'No organization found'], 403);
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:money_movements,id',
        ]);

        DB::beginTransaction();
        try {
            $deleted = MoneyMovement::where('organization_id', $organizationId)
                ->whereIn('id', $validated['ids'])
                ->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => "{$deleted} transaction(s) deleted successfully"]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to delete transactions'], 500);
        }
    }

    public function verify(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return response()->json(['error' => 'No organization found'], 403);
        }

        // Get only unverified income transactions (potential invoice payments)
        $recentTransactions = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('transaction_date', '>=', now()->subDays(90))
            ->where('is_verified', false) // Only unverified transactions
            ->whereNull('related_id') // Not already linked
            ->with('toAccount')
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Get outstanding invoices
        $outstandingInvoices = Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->whereRaw('total_amount > paid_amount')
            ->with('customer')
            ->orderBy('invoice_date', 'desc')
            ->get();

        // Get unallocated payments
        $payments = Payment::where('organization_id', $organizationId)
            ->with(['allocations', 'customer'])
            ->get()
            ->filter(function($payment) {
                $allocated = $payment->allocations->sum('amount');
                return $payment->amount > $allocated;
            });

        $invoiceMatches = [];
        $allocationSuggestions = [];
        $discrepancies = [];

        // Match transactions to invoices
        foreach ($recentTransactions as $transaction) {
            $bestMatch = null;
            $bestConfidence = 0;

            foreach ($outstandingInvoices as $invoice) {
                $outstanding = $invoice->total_amount - $invoice->paid_amount;
                
                // Check amount match (within 5% tolerance)
                $amountMatch = abs($transaction->amount - $outstanding) / max($outstanding, 1) <= 0.05;
                
                // Check date match (transaction after invoice date, within 60 days)
                $dateMatch = $transaction->transaction_date >= $invoice->invoice_date 
                    && $transaction->transaction_date <= $invoice->invoice_date->copy()->addDays(60);
                
                // Check description match (look for invoice number or customer name)
                $descriptionMatch = false;
                $description = strtolower($transaction->description);
                if (stripos($description, $invoice->invoice_number) !== false) {
                    $descriptionMatch = true;
                } elseif ($invoice->customer && stripos($description, strtolower($invoice->customer->name)) !== false) {
                    $descriptionMatch = true;
                }

                // Calculate confidence score
                $confidence = 0;
                if ($amountMatch) $confidence += 0.5;
                if ($dateMatch) $confidence += 0.3;
                if ($descriptionMatch) $confidence += 0.2;

                if ($confidence > $bestConfidence && $confidence >= 0.5) {
                    $bestConfidence = $confidence;
                    $bestMatch = [
                        'transaction_id' => $transaction->id,
                        'transaction_description' => $transaction->description,
                        'transaction_amount' => $transaction->amount,
                        'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->customer->name ?? 'Unknown',
                        'invoice_outstanding' => $outstanding,
                        'confidence' => $confidence,
                    ];
                }
            }

            if ($bestMatch) {
                $invoiceMatches[] = $bestMatch;
            }
        }

        // Generate payment allocation suggestions
        foreach ($payments as $payment) {
            $allocated = $payment->allocations->sum('amount');
            $unallocated = $payment->amount - $allocated;

            if ($unallocated <= 0) continue;

            // Get customer's outstanding invoices
            $customerInvoices = $outstandingInvoices
                ->where('customer_id', $payment->customer_id)
                ->sortBy('invoice_date');

            $suggestedAllocations = [];
            $remainingUnallocated = $unallocated;

            foreach ($customerInvoices as $invoice) {
                if ($remainingUnallocated <= 0) break;

                $outstanding = $invoice->total_amount - $invoice->paid_amount;
                if ($outstanding <= 0) continue;

                $suggestedAmount = min($remainingUnallocated, $outstanding);
                $suggestedAllocations[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $suggestedAmount,
                    'outstanding' => $outstanding,
                ];

                $remainingUnallocated -= $suggestedAmount;
            }

            if (count($suggestedAllocations) > 0) {
                $allocationSuggestions[] = [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'payment_amount' => $payment->amount,
                    'unallocated_amount' => $unallocated,
                    'suggested_allocations' => $suggestedAllocations,
                ];
            }
        }

        // Find discrepancies
        // 1. Transactions with amounts that don't match any invoice
        foreach ($recentTransactions as $transaction) {
            $hasMatch = collect($invoiceMatches)->contains('transaction_id', $transaction->id);
            if (!$hasMatch && $transaction->amount > 100) { // Only flag significant amounts
                $discrepancies[] = [
                    'type' => 'Unmatched Transaction',
                    'description' => "Transaction '{$transaction->description}' ({$transaction->amount} ZMW) doesn't match any outstanding invoice",
                    'transaction_id' => $transaction->id,
                ];
            }
        }

        // 2. Invoices with payments that haven't been allocated
        foreach ($outstandingInvoices as $invoice) {
            $outstanding = $invoice->total_amount - $invoice->paid_amount;
            if ($outstanding > 0) {
                // Check if there are payments from this customer that could match
                $customerPayments = $payments->where('customer_id', $invoice->customer_id);
                $hasPotentialMatch = $customerPayments->some(function($payment) use ($outstanding) {
                    $allocated = $payment->allocations->sum('amount');
                    $unallocated = $payment->amount - $allocated;
                    return abs($unallocated - $outstanding) / max($outstanding, 1) <= 0.1;
                });

                if (!$hasPotentialMatch && $outstanding > 100) {
                    $discrepancies[] = [
                        'type' => 'Unpaid Invoice',
                        'description' => "Invoice #{$invoice->invoice_number} has {$outstanding} ZMW outstanding with no matching payment",
                        'invoice_id' => $invoice->id,
                    ];
                }
            }
        }

        return response()->json([
            'matched_count' => count($invoiceMatches),
            'suggestions_count' => count($allocationSuggestions),
            'discrepancies_count' => count($discrepancies),
            'invoice_matches' => $invoiceMatches,
            'allocation_suggestions' => $allocationSuggestions,
            'discrepancies' => $discrepancies,
        ]);
    }

    public function matchInvoice(Request $request, $id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return response()->json(['error' => 'No organization found'], 403);
        }

        $validated = $request->validate([
            'invoice_id' => 'required|uuid|exists:invoices,id',
        ]);

        $transaction = MoneyMovement::where('organization_id', $organizationId)
            ->findOrFail($id);

        $invoice = Invoice::where('organization_id', $organizationId)
            ->findOrFail($validated['invoice_id']);

        DB::beginTransaction();
        try {
            // Load customer relationship if not already loaded
            $invoice->load('customer');
            $customerName = $invoice->customer->name ?? 'Unknown';
            
            // Update transaction category to link it to the invoice and mark as verified
            $transaction->update([
                'category' => "Invoice #{$invoice->invoice_number} | Payment from {$customerName}",
                'related_type' => Invoice::class,
                'related_id' => $invoice->id,
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by_id' => Auth::id(),
            ]);

            // Create or update payment allocation if a payment exists
            $payment = Payment::where('organization_id', $organizationId)
                ->where('customer_id', $invoice->customer_id)
                ->where('amount', $transaction->amount)
                ->where('payment_date', $transaction->transaction_date)
                ->first();

            if (!$payment) {
                // Create a payment record
                $payment = Payment::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organizationId,
                    'customer_id' => $invoice->customer_id,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'payment_date' => $transaction->transaction_date,
                    'payment_method' => 'bank_transfer',
                    'money_account_id' => $transaction->to_account_id,
                ]);
            }

            // Create payment allocation
            $outstanding = $invoice->total_amount - $invoice->paid_amount;
            $allocationAmount = min($transaction->amount, $outstanding);

            if ($allocationAmount > 0) {
                PaymentAllocation::create([
                    'id' => (string) Str::uuid(),
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $allocationAmount,
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Transaction matched to invoice successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to match transaction to invoice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to match transaction'], 500);
        }
    }

    public function markAsVerified(Request $request, $id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return response()->json(['error' => 'No organization found'], 403);
        }

        $transaction = MoneyMovement::where('organization_id', $organizationId)
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            $transaction->update([
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Transaction marked as verified']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to mark transaction as verified', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to mark transaction as verified'], 500);
        }
    }
}
