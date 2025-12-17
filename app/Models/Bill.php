<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'vendor_id',
        'created_by',
        'bill_number',
        'vendor_invoice_number',
        'status',
        'payment_status',
        'bill_date',
        'due_date',
        'received_date',
        'approved_date',
        'approved_by',
        'amount', // Required field for backward compatibility
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'amount_paid',
        'amount_due',
        'currency',
        'category',
        'department',
        'project',
        'payment_method',
        'payment_reference',
        'is_recurring',
        'recurring_frequency',
        'next_recurrence_date',
        'attachments',
        'description',
        'internal_notes',
        'notes',
        'tags',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'received_date' => 'date',
        'approved_date' => 'date',
        'next_recurrence_date' => 'date',
        'amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'is_recurring' => 'boolean',
        'attachments' => 'array',
        'tags' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bill) {
            if (empty($bill->bill_number)) {
                $bill->bill_number = self::generateBillNumber();
            }
            // Set amount from total if not provided (for backward compatibility)
            if (!isset($bill->amount) && isset($bill->total)) {
                $bill->amount = $bill->total;
            }
            // Calculate amount_due if total is set
            if (isset($bill->total) && !isset($bill->amount_due)) {
                $bill->amount_due = $bill->total - ($bill->amount_paid ?? 0);
            }
        });
    }

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    // Scopes
    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', ['unpaid', 'partially_paid']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('payment_status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->where('status', '!=', 'cancelled');
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('payment_status', '!=', 'paid')
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->where('status', '!=', 'cancelled');
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->payment_status !== 'paid' && 
               $this->due_date < now() && 
               $this->status !== 'cancelled';
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }
        // Return positive days overdue
        return (int) abs(now()->diffInDays($this->due_date, false));
    }

    public function getDaysUntilDueAttribute(): int
    {
        return max(0, now()->diffInDays($this->due_date, false));
    }

    // Methods
    public static function generateBillNumber(): string
    {
        $prefix = 'BILL';
        $year = date('Y');
        $lastBill = self::whereYear('created_at', $year)->latest('id')->first();
        $number = $lastBill ? ((int) substr($lastBill->bill_number, -4)) + 1 : 1;
        
        return $prefix . '-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->total = $this->subtotal - ($this->discount_amount ?? 0) + ($this->tax_amount ?? 0);
        $this->amount_due = $this->total - ($this->amount_paid ?? 0);
        
        $this->updatePaymentStatus();
        $this->save();
    }

    public function updatePaymentStatus(): void
    {
        $amountPaid = $this->amount_paid ?? 0;
        $total = $this->total ?? 0;

        if ($amountPaid == 0) {
            $this->payment_status = 'unpaid';
        } elseif ($amountPaid >= $total) {
            $this->payment_status = 'paid';
        } else {
            $this->payment_status = 'partially_paid';
        }

        // Update status to overdue if applicable
        if ($this->is_overdue && $this->status !== 'cancelled') {
            $this->status = 'overdue';
        }
        
        $this->save();
    }

    public function recordPayment(float $amount, array $paymentData = []): BillPayment
    {
        $user = auth()->user();
        
        // Remove bank_account_id if it's set but table doesn't exist
        $paymentData = array_filter($paymentData, function($key) {
            return $key !== 'bank_account_id';
        }, ARRAY_FILTER_USE_KEY);
        
        $payment = $this->payments()->create(array_merge([
            'organization_id' => $this->organization_id,
            'vendor_id' => $this->vendor_id,
            'created_by' => $user ? $user->id : $this->created_by,
            'amount' => $amount,
            'currency' => $this->currency ?? 'ZMW',
            'payment_date' => now(),
            'payment_method' => $paymentData['payment_method'] ?? 'bank_transfer',
            'status' => 'completed',
            'bank_account_id' => null, // Explicitly set to null to avoid foreign key issue
        ], $paymentData));

        $this->amount_paid = ($this->amount_paid ?? 0) + $amount;
        $this->amount_due = ($this->total ?? 0) - $this->amount_paid;
        $this->updatePaymentStatus();
        $this->save();

        // Update vendor financial metrics if method exists
        if (method_exists($this->vendor, 'updateFinancialMetrics')) {
            $this->vendor->updateFinancialMetrics();
        }

        return $payment;
    }

    public function approve($userId): void
    {
        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_date = now();
        $this->save();
    }

    public function markAsPaid(): void
    {
        $this->payment_status = 'paid';
        $this->status = 'paid';
        $this->save();
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    public function checkAndMarkOverdue(): void
    {
        if ($this->is_overdue) {
            $this->status = 'overdue';
            $this->save();
        }
    }

    public function generateNextRecurrence(): ?Bill
    {
        if (!$this->is_recurring || !$this->next_recurrence_date) {
            return null;
        }

        $newBill = $this->replicate();
        $newBill->bill_number = null;
        $newBill->bill_date = $this->next_recurrence_date;
        
        $daysDiff = $this->due_date->diffInDays($this->bill_date);
        $newBill->due_date = $newBill->bill_date->copy()->addDays($daysDiff);
        
        $newBill->status = 'draft';
        $newBill->payment_status = 'unpaid';
        $newBill->amount_paid = 0;
        $newBill->amount_due = $newBill->total;
        $newBill->save();

        // Copy items if they exist (using DB insert to bypass foreign key issues)
        if ($this->items()->exists()) {
            foreach ($this->items as $item) {
                \Illuminate\Support\Facades\DB::table('bill_items')->insert([
                    'bill_id' => $newBill->id,
                    'product_id' => null,
                    'order' => $item->order,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit ?? 'unit',
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount ?? 0,
                    'tax_amount' => $item->tax_amount ?? 0,
                    'total' => $item->total,
                    'account_code' => $item->account_code,
                    'cost_center' => $item->cost_center,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Update next recurrence date
        if ($this->recurring_frequency) {
            $this->next_recurrence_date = match($this->recurring_frequency) {
                'weekly' => $this->next_recurrence_date->addWeek(),
                'monthly' => $this->next_recurrence_date->addMonth(),
                'quarterly' => $this->next_recurrence_date->addMonths(3),
                'yearly' => $this->next_recurrence_date->addYear(),
                default => null,
            };
            $this->save();
        }

        return $newBill;
    }
}
