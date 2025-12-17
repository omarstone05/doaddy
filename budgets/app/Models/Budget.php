<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends BaseModel
{
    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'budget_number',
        'start_date',
        'end_date',
        'period_type',
        'currency_code',
        'total_amount',
        'allocated_amount',
        'spent_amount',
        'committed_amount',
        'status',
        'health_status',
        'owner_id',
        'department',
        'project_id',
        'template_id',
        'parent_budget_id',
        'version',
        'tags',
        'custom_fields',
        'allow_overspend',
        'require_approval',
        'alert_threshold',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'tags' => 'array',
        'custom_fields' => 'array',
        'allow_overspend' => 'boolean',
        'require_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class);
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'budget_collaborators')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BudgetApproval::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BudgetComment::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(BudgetInsight::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(BudgetAlert::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BudgetTemplate::class);
    }
}
