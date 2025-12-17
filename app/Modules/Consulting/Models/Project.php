<?php

namespace App\Modules\Consulting\Models;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Project Model
 * 
 * Core model for the Consulting module
 * Represents a complete project container
 */
class Project extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'consulting_projects';

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'type',
        'description',
        'status',
        'client_id',
        'client_name',
        'project_manager_id',
        'lead_id',
        'team_members',
        'client_contacts',
        'budget_total',
        'budget_breakdown',
        'billing_model',
        'rate_per_hour',
        'start_date',
        'end_date',
        'actual_start_date',
        'actual_end_date',
        'progress_percentage',
        'health_status',
        'client_portal_enabled',
        'internal_access_rules',
        'client_visibility_rules',
        'custom_fields',
        'tags',
        'priority',
    ];

    protected $casts = [
        'team_members' => 'array',
        'client_contacts' => 'array',
        'budget_breakdown' => 'array',
        'internal_access_rules' => 'array',
        'client_visibility_rules' => 'array',
        'custom_fields' => 'array',
        'tags' => 'array',
        'budget_total' => 'decimal:2',
        'rate_per_hour' => 'decimal:2',
        'progress_percentage' => 'integer',
        'client_portal_enabled' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    /**
     * Relationships
     */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function projectManager()
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }

    public function deliverables()
    {
        return $this->hasMany(Deliverable::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function changeOrders()
    {
        return $this->hasMany(ChangeOrder::class);
    }

    public function risks()
    {
        return $this->hasMany(Risk::class);
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function communications()
    {
        return $this->hasMany(Communication::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Scopes
     */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeManagedBy($query, $userId)
    {
        return $query->where('project_manager_id', $userId);
    }

    public function scopeWithClient($query)
    {
        return $query->whereNotNull('client_id');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Computed Properties
     */

    public function getTotalExpensesAttribute()
    {
        return $this->expenses()->sum('amount');
    }

    public function getBudgetRemainingAttribute()
    {
        return $this->budget_total - $this->total_expenses;
    }

    public function getCompletedTasksCountAttribute()
    {
        return $this->tasks()->where('status', 'completed')->count();
    }

    public function getTotalTasksCountAttribute()
    {
        return $this->tasks()->count();
    }

    public function getTotalBillableHoursAttribute()
    {
        return $this->timeEntries()
            ->where('billable', true)
            ->sum('minutes') / 60;
    }

    public function getTotalBillableAmountAttribute()
    {
        return $this->timeEntries()
            ->where('billable', true)
            ->selectRaw('SUM(minutes * hourly_rate / 60) as total')
            ->value('total') ?? 0;
    }

    public function getIsDelayedAttribute()
    {
        if (!$this->end_date) {
            return false;
        }

        return $this->end_date->isPast() && $this->status !== 'complete';
    }

    public function getDaysRemainingAttribute()
    {
        if (!$this->end_date) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    /**
     * Helper Methods
     */

    public function updateHealthStatus(): void
    {
        // Calculate health based on budget, timeline, tasks, issues
        $budgetHealth = $this->budget_remaining > 0 ? 'good' : 'poor';
        $timelineHealth = $this->is_delayed ? 'poor' : 'good';

        if ($budgetHealth === 'poor' || $timelineHealth === 'poor') {
            $health = 'at_risk';
        } else {
            $health = 'on_track';
        }

        $this->update(['health_status' => $health]);
    }

    public function updateProgress(): void
    {
        $totalTasks = $this->tasks()->count();
        
        if ($totalTasks === 0) {
            $this->update(['progress_percentage' => 0]);
            return;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        $progress = ($completedTasks / $totalTasks) * 100;

        $this->update(['progress_percentage' => round($progress)]);
    }

    public function canBeAccessedByUser(User $user): bool
    {
        if ($this->project_manager_id === $user->id) {
            return true;
        }

        if ($this->lead_id === $user->id) {
            return true;
        }

        if (in_array($user->id, $this->team_members ?? [])) {
            return true;
        }

        return $user->organizations->contains($this->organization_id);
    }
}

