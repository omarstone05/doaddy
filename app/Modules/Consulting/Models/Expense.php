<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Expense extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'consulting_expenses';

    protected $fillable = [
        'project_id',
        'task_id',
        'description',
        'category',
        'amount',
        'currency',
        'expense_date',
        'vendor_id',
        'vendor_name',
        'receipt_file',
        'receipt_number',
        'approval_status',
        'approved_by',
        'approved_at',
        'billable_to_client',
        'billed',
        'markup_percentage',
        'paid',
        'paid_at',
        'notes',
        'custom_fields',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'billable_to_client' => 'boolean',
        'billed' => 'boolean',
        'paid' => 'boolean',
        'expense_date' => 'date',
        'paid_at' => 'date',
        'approved_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeBillable($query)
    {
        return $query->where('billable_to_client', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('paid', false);
    }
}

