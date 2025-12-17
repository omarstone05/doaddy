# PENDA BUDGET SERVICE - V1 IMPLEMENTATION GUIDE
## Domain: budgets.penda.digital

---

## TABLE OF CONTENTS
1. [Project Overview](#project-overview)
2. [Technical Stack](#technical-stack)
3. [Database Schema](#database-schema)
4. [Laravel Migrations](#laravel-migrations)
5. [Eloquent Models](#eloquent-models)
6. [API Architecture](#api-architecture)
7. [Authentication & Authorization](#authentication--authorization)
8. [Business Logic](#business-logic)
9. [AI Integration](#ai-integration)
10. [Frontend Components](#frontend-components)
11. [Deployment Guide](#deployment-guide)
12. [Testing Strategy](#testing-strategy)

---

## PROJECT OVERVIEW

### Purpose
Standalone microservice providing budget management for:
- **Addy Business** - Company-wide budgets (departmental, operational)
- **Projjo** - Project-specific budgets
- **LiDe** - Personal budgets (Life by Design)

### V1 Core Features
1. ✅ Budget Creation with flexible periods
2. ✅ Budget Templates by industry
3. ✅ Multi-level Approval Workflows (configurable)
4. ✅ Real-time Budget Reconciliation
5. ✅ AI Budget Insights & Alerts
6. ✅ Expense Tracking with Auto-categorization
7. ✅ Dashboard Cards with visualizations
8. ✅ Budget Collaboration (multiple users, single owner)
9. ✅ Multi-currency support (per organization)
10. ✅ Receipt/Statement upload with OCR

### Key Principles
- **Real-time updates** - No batch processing
- **AI-powered** - Smart categorization and insights
- **Flexible** - 1-day to multi-year budgets
- **Collaborative** - Multiple users per budget
- **Audit-friendly** - Complete history tracking

---

## TECHNICAL STACK

### Backend
- **Framework:** Laravel 11.x
- **Database:** PostgreSQL 16+
- **Cache:** Redis
- **Queue:** Redis Queue
- **Storage:** S3-compatible (Wasabi/DigitalOcean Spaces)

### Authentication
- **Method:** JWT (JSON Web Tokens)
- **Package:** tymon/jwt-auth
- **Flow:** Parent apps issue tokens with user context

### AI/ML
- **Provider:** OpenAI GPT-4
- **Use Cases:** 
  - Transaction categorization
  - Spending pattern analysis
  - Budget recommendations
  - Anomaly detection
  - OCR for receipts

### External Services
- **OCR:** Tesseract OCR / AWS Textract
- **Notifications:** 
  - Email: AWS SES
  - SMS: Africa's Talking
  - WhatsApp: WhatsApp Cloud API

### Development
- **Version Control:** Git
- **CI/CD:** GitHub Actions
- **API Documentation:** Scribe / OpenAPI
- **Testing:** PHPUnit, Pest

---

## DATABASE SCHEMA

### Core Tables Structure

```sql
-- Multi-tenancy
organizations (id, name, type, parent_app, parent_id, currency_code, settings)
users (id, organization_id, parent_user_id, name, email, role)

-- Budget Structure
budgets (id, organization_id, name, start_date, end_date, total_amount, status, owner_id)
budget_items (id, budget_id, category_id, name, budgeted_amount, spent_amount)
budget_categories (id, organization_id, parent_id, name, icon, color)
budget_templates (id, organization_id, name, industry, template_data)

-- Collaboration
budget_collaborators (id, budget_id, user_id, role, permissions)
budget_comments (id, budget_id, user_id, comment)

-- Approval System
budget_approval_workflows (id, organization_id, name, stages)
budget_approvals (id, budget_id, approver_id, status, modifications)

-- Transactions
budget_transactions (id, budget_id, budget_item_id, amount, transaction_date, source_app)
budget_reconciliations (id, budget_id, budgeted_amount, actual_amount, variance_amount)

-- AI & Intelligence
budget_insights (id, budget_id, insight_type, title, description, ai_model)
budget_alerts (id, budget_id, alert_type, severity, message)
budget_forecasts (id, budget_id, forecast_date, predicted_spend)

-- Analytics
budget_dashboard_cards (id, organization_id, card_type, data_config)
budget_audit_log (id, budget_id, user_id, action, old_values, new_values)
```

### Key Relationships
- Organization → Users (1:N)
- Organization → Budgets (1:N)
- Budget → Budget Items (1:N)
- Budget → Collaborators (N:N through budget_collaborators)
- Budget → Transactions (1:N)
- Budget → Approvals (1:N)

### Database Triggers
1. **update_budget_spent_amount()** - Real-time spent calculation
2. **update_budget_health_status()** - Auto health status updates
3. **update_budget_allocated_amount()** - Sum of budget items

---

## LARAVEL MIGRATIONS

### Installation Commands
```bash
# Create new Laravel project
composer create-project laravel/laravel penda-budget-service
cd penda-budget-service

# Install dependencies
composer require tymon/jwt-auth
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require openai-php/laravel

# Database setup
php artisan make:migration create_budget_tables
```

### Migration Files

**File: database/migrations/xxxx_create_organizations_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['company', 'project', 'personal']);
            $table->enum('parent_app', ['addy', 'projjo', 'lide']);
            $table->string('parent_id')->index();
            $table->string('currency_code', 3)->default('ZMW');
            $table->string('timezone', 50)->default('Africa/Lusaka');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['parent_app', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
```

**File: database/migrations/xxxx_create_budgets_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            
            // Basic Info
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('budget_number', 50)->unique();
            
            // Period
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'annual', 'custom'])->default('custom');
            
            // Amounts
            $table->string('currency_code', 3);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0);
            
            // Status
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'active', 'closed', 'cancelled'])->default('draft');
            $table->enum('health_status', ['healthy', 'warning', 'danger', 'overspent'])->default('healthy');
            
            // Ownership
            $table->foreignUuid('owner_id')->constrained('users');
            $table->string('department', 100)->nullable();
            $table->string('project_id')->nullable()->index();
            
            // Metadata
            $table->foreignUuid('template_id')->nullable()->constrained('budget_templates');
            $table->foreignUuid('parent_budget_id')->nullable()->constrained('budgets');
            $table->integer('version')->default(1);
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            
            // Settings
            $table->boolean('allow_overspend')->default(false);
            $table->boolean('require_approval')->default(true);
            $table->integer('alert_threshold')->default(80);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['organization_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
```

**File: database/migrations/xxxx_create_budget_items_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('budget_categories');
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('item_code', 50)->nullable();
            
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0);
            
            $table->enum('item_type', ['expense', 'income'])->default('expense');
            $table->enum('frequency', ['one_time', 'recurring_monthly', 'recurring_quarterly', 'recurring_annual'])->default('one_time');
            
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
```

**File: database/migrations/xxxx_create_budget_transactions_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('budget_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('budget_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('budget_categories');
            
            $table->date('transaction_date');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3);
            $table->enum('transaction_type', ['expense', 'income'])->default('expense');
            
            // Source integration
            $table->enum('source_app', ['addy', 'projjo', 'lide', 'manual'])->nullable();
            $table->string('source_id')->nullable()->index();
            $table->json('source_data')->nullable();
            
            // AI Categorization
            $table->boolean('is_auto_categorized')->default(false);
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->boolean('category_overridden')->default(false);
            
            // Reconciliation
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignUuid('reconciled_by')->nullable()->constrained('users');
            
            // Receipt
            $table->text('receipt_url')->nullable();
            $table->json('receipt_data')->nullable();
            
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['budget_id', 'transaction_date']);
            $table->index(['source_app', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_transactions');
    }
};
```

### Additional Migrations (abbreviated)
```php
// budget_categories, budget_templates, budget_collaborators,
// budget_approval_workflows, budget_approvals, budget_comments,
// budget_insights, budget_alerts, budget_forecasts,
// budget_dashboard_cards, budget_audit_log
// ... (follow same pattern as above)
```

---

## ELOQUENT MODELS

### Base Model Setup

**File: app/Models/BaseModel.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
```

### Organization Model

**File: app/Models/Organization.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends BaseModel
{
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(BudgetTemplate::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForApp($query, string $app)
    {
        return $query->where('parent_app', $app);
    }

    // Helpers
    public function getApprovalWorkflow(array $conditions = []): ?BudgetApprovalWorkflow
    {
        $workflows = $this->approvalWorkflows()->active()->get();
        
        foreach ($workflows as $workflow) {
            if ($this->matchesWorkflowConditions($workflow, $conditions)) {
                return $workflow;
            }
        }
        
        return $workflows->where('is_default', true)->first();
    }

    private function matchesWorkflowConditions($workflow, $conditions): bool
    {
        $triggerConditions = $workflow->trigger_conditions ?? [];
        
        if (empty($triggerConditions)) {
            return false;
        }
        
        // Check amount threshold
        if (isset($triggerConditions['min_amount']) && 
            isset($conditions['amount']) && 
            $conditions['amount'] < $triggerConditions['min_amount']) {
            return false;
        }
        
        // Check department
        if (isset($triggerConditions['departments']) && 
            isset($conditions['department']) && 
            !in_array($conditions['department'], $triggerConditions['departments'])) {
            return false;
        }
        
        return true;
    }
}
```

### Budget Model

**File: app/Models/Budget.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Budget extends BaseModel
{
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

    // Relationships
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

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BudgetReconciliation::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BudgetTemplate::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByHealthStatus($query, string $healthStatus)
    {
        return $query->where('health_status', $healthStatus);
    }

    public function scopeActivePeriod($query)
    {
        $today = now()->toDateString();
        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    public function scopeOverspent($query)
    {
        return $query->whereRaw('spent_amount > total_amount');
    }

    // Accessors
    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->spent_amount - $this->committed_amount;
    }

    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }
        return round(($this->spent_amount / $this->total_amount) * 100, 2);
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getDailyBurnRateAttribute(): float
    {
        $daysElapsed = max(1, $this->start_date->diffInDays(now()));
        return $this->spent_amount / $daysElapsed;
    }

    public function getProjectedSpendAttribute(): float
    {
        $totalDays = max(1, $this->start_date->diffInDays($this->end_date));
        return $this->daily_burn_rate * $totalDays;
    }

    // Methods
    public function generateBudgetNumber(): string
    {
        $prefix = 'BDG';
        $year = $this->start_date->format('Y');
        $lastNumber = self::where('organization_id', $this->organization_id)
            ->where('budget_number', 'like', "{$prefix}-{$year}-%")
            ->count();
        
        return sprintf('%s-%s-%03d', $prefix, $year, $lastNumber + 1);
    }

    public function canBeModifiedBy(User $user): bool
    {
        // Owner can always modify
        if ($this->owner_id === $user->id) {
            return true;
        }

        // Check collaborator permissions
        $collaborator = $this->collaborators()->where('user_id', $user->id)->first();
        if ($collaborator && $collaborator->pivot->role === 'editor') {
            return true;
        }

        // Check organization role
        return $user->role === 'admin' || $user->role === 'owner';
    }

    public function submitForApproval(): void
    {
        if ($this->status !== 'draft') {
            throw new \Exception('Only draft budgets can be submitted for approval');
        }

        $workflow = $this->organization->getApprovalWorkflow([
            'amount' => $this->total_amount,
            'department' => $this->department,
        ]);

        if (!$workflow) {
            // No approval needed, auto-approve
            $this->update(['status' => 'approved']);
            return;
        }

        // Create approval records
        $stages = $workflow->stages ?? [];
        foreach ($stages as $index => $stage) {
            BudgetApproval::create([
                'budget_id' => $this->id,
                'workflow_id' => $workflow->id,
                'stage_number' => $index + 1,
                'stage_name' => $stage['name'] ?? "Stage " . ($index + 1),
                'approver_id' => $stage['approver_id'],
                'status' => $index === 0 ? 'pending' : 'waiting',
            ]);
        }

        $this->update(['status' => 'pending_approval']);
    }

    public function recalculateAllocated(): void
    {
        $this->update([
            'allocated_amount' => $this->items()->sum('budgeted_amount'),
        ]);
    }

    public function recalculateSpent(): void
    {
        $this->update([
            'spent_amount' => $this->transactions()
                ->where('is_reconciled', true)
                ->sum('amount'),
        ]);
    }

    public function checkAlerts(): void
    {
        $utilizationPercentage = $this->utilization_percentage;

        // Check threshold alert
        if ($utilizationPercentage >= $this->alert_threshold && 
            $utilizationPercentage < 100 &&
            $this->health_status !== 'overspent') {
            
            $this->createAlert(
                'threshold_reached',
                'warning',
                'Budget Alert: Threshold Reached',
                "Your budget '{$this->name}' has reached {$utilizationPercentage}% utilization."
            );
        }

        // Check overspend alert
        if ($utilizationPercentage >= 100 && !$this->allow_overspend) {
            $this->createAlert(
                'overspend',
                'critical',
                'Budget Alert: Overspent',
                "Your budget '{$this->name}' has exceeded its limit."
            );
        }
    }

    private function createAlert(string $type, string $severity, string $title, string $message): void
    {
        // Check if alert already exists and is unresolved
        $existingAlert = $this->alerts()
            ->where('alert_type', $type)
            ->where('is_resolved', false)
            ->first();

        if (!$existingAlert) {
            BudgetAlert::create([
                'organization_id' => $this->organization_id,
                'budget_id' => $this->id,
                'alert_type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'threshold_percentage' => $this->alert_threshold,
                'current_percentage' => $this->utilization_percentage,
            ]);
        }
    }
}
```

### Budget Item Model

**File: app/Models/BudgetItem.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetItem extends BaseModel
{
    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    protected $appends = ['remaining_amount', 'utilization_percentage', 'health_status'];

    // Relationships
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class);
    }

    // Accessors
    public function getRemainingAmountAttribute(): float
    {
        return $this->budgeted_amount - $this->spent_amount - $this->committed_amount;
    }

    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->budgeted_amount <= 0) {
            return 0;
        }
        return round(($this->spent_amount / $this->budgeted_amount) * 100, 2);
    }

    public function getHealthStatusAttribute(): string
    {
        $utilization = $this->utilization_percentage;
        
        if ($utilization >= 100) {
            return 'overspent';
        } elseif ($utilization >= 90) {
            return 'danger';
        } elseif ($utilization >= 80) {
            return 'warning';
        }
        
        return 'healthy';
    }
}
```

### Budget Transaction Model

**File: app/Models/BudgetTransaction.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransaction extends BaseModel
{
    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'ai_confidence' => 'decimal:2',
        'is_auto_categorized' => 'boolean',
        'category_overridden' => 'boolean',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
        'source_data' => 'array',
        'receipt_data' => 'array',
        'tags' => 'array',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    // Scopes
    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Methods
    public function reconcile(User $user): void
    {
        $this->update([
            'is_reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by' => $user->id,
        ]);

        // Trigger budget recalculation
        if ($this->budget) {
            $this->budget->recalculateSpent();
            $this->budget->checkAlerts();
        }
    }

    public function categorizeWithAI(): void
    {
        // This will be implemented in the AI service
        app(\App\Services\AIService::class)->categorizeTransaction($this);
    }
}
```

---

## API ARCHITECTURE

### Route Structure

**File: routes/api.php**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1;

Route::prefix('v1')->group(function () {
    
    // Authentication
    Route::post('auth/register-organization', [V1\AuthController::class, 'registerOrganization']);
    Route::post('auth/verify-token', [V1\AuthController::class, 'verifyToken']);
    
    Route::middleware(['auth:api'])->group(function () {
        
        // Organizations
        Route::apiResource('organizations', V1\OrganizationController::class);
        Route::get('organizations/{organization}/stats', [V1\OrganizationController::class, 'stats']);
        
        // Budget Categories
        Route::apiResource('categories', V1\BudgetCategoryController::class);
        Route::post('categories/bulk', [V1\BudgetCategoryController::class, 'bulkCreate']);
        
        // Budget Templates
        Route::apiResource('templates', V1\BudgetTemplateController::class);
        Route::post('templates/{template}/clone', [V1\BudgetTemplateController::class, 'clone']);
        
        // Budgets
        Route::apiResource('budgets', V1\BudgetController::class);
        Route::get('budgets/{budget}/dashboard', [V1\BudgetController::class, 'dashboard']);
        Route::post('budgets/{budget}/submit-approval', [V1\BudgetController::class, 'submitForApproval']);
        Route::post('budgets/{budget}/clone', [V1\BudgetController::class, 'clone']);
        Route::post('budgets/{budget}/close', [V1\BudgetController::class, 'close']);
        Route::get('budgets/{budget}/variance-report', [V1\BudgetController::class, 'varianceReport']);
        
        // Budget Items
        Route::apiResource('budgets.items', V1\BudgetItemController::class);
        Route::post('budgets/{budget}/items/bulk', [V1\BudgetItemController::class, 'bulkCreate']);
        
        // Budget Collaborators
        Route::get('budgets/{budget}/collaborators', [V1\BudgetCollaboratorController::class, 'index']);
        Route::post('budgets/{budget}/collaborators', [V1\BudgetCollaboratorController::class, 'store']);
        Route::delete('budgets/{budget}/collaborators/{user}', [V1\BudgetCollaboratorController::class, 'destroy']);
        
        // Budget Approvals
        Route::get('budgets/{budget}/approvals', [V1\BudgetApprovalController::class, 'index']);
        Route::post('approvals/{approval}/approve', [V1\BudgetApprovalController::class, 'approve']);
        Route::post('approvals/{approval}/reject', [V1\BudgetApprovalController::class, 'reject']);
        Route::post('approvals/{approval}/modify', [V1\BudgetApprovalController::class, 'modify']);
        
        // Budget Comments
        Route::apiResource('budgets.comments', V1\BudgetCommentController::class);
        
        // Transactions
        Route::apiResource('transactions', V1\BudgetTransactionController::class);
        Route::post('transactions/bulk', [V1\BudgetTransactionController::class, 'bulkCreate']);
        Route::post('transactions/{transaction}/reconcile', [V1\BudgetTransactionController::class, 'reconcile']);
        Route::post('transactions/{transaction}/categorize', [V1\BudgetTransactionController::class, 'categorize']);
        Route::post('transactions/upload-receipt', [V1\BudgetTransactionController::class, 'uploadReceipt']);
        Route::post('transactions/upload-statement', [V1\BudgetTransactionController::class, 'uploadStatement']);
        
        // Reconciliations
        Route::get('budgets/{budget}/reconciliations', [V1\BudgetReconciliationController::class, 'index']);
        Route::post('budgets/{budget}/reconcile', [V1\BudgetReconciliationController::class, 'reconcile']);
        
        // AI Insights
        Route::get('insights', [V1\AIInsightController::class, 'index']);
        Route::get('budgets/{budget}/insights', [V1\AIInsightController::class, 'forBudget']);
        Route::post('insights/{insight}/dismiss', [V1\AIInsightController::class, 'dismiss']);
        Route::post('budgets/{budget}/generate-insights', [V1\AIInsightController::class, 'generate']);
        
        // Alerts
        Route::get('alerts', [V1\BudgetAlertController::class, 'index']);
        Route::get('budgets/{budget}/alerts', [V1\BudgetAlertController::class, 'forBudget']);
        Route::post('alerts/{alert}/resolve', [V1\BudgetAlertController::class, 'resolve']);
        Route::post('alerts/{alert}/snooze', [V1\BudgetAlertController::class, 'snooze']);
        
        // Forecasts
        Route::get('budgets/{budget}/forecast', [V1\BudgetForecastController::class, 'forecast']);
        
        // Dashboard
        Route::get('dashboard', [V1\DashboardController::class, 'index']);
        Route::get('dashboard/cards', [V1\DashboardController::class, 'cards']);
        Route::post('dashboard/cards', [V1\DashboardController::class, 'saveCardConfiguration']);
        
        // Reports
        Route::get('reports/budget-summary', [V1\ReportController::class, 'budgetSummary']);
        Route::get('reports/spending-trends', [V1\ReportController::class, 'spendingTrends']);
        Route::get('reports/category-breakdown', [V1\ReportController::class, 'categoryBreakdown']);
        Route::get('reports/variance-analysis', [V1\ReportController::class, 'varianceAnalysis']);
        
        // Audit Log
        Route::get('audit-log', [V1\AuditLogController::class, 'index']);
        Route::get('budgets/{budget}/audit-log', [V1\AuditLogController::class, 'forBudget']);
    });
});
```

### Controller Examples

**File: app/Http/Controllers/Api/V1/BudgetController.php**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $budgets = Budget::query()
            ->forOrganization($request->user()->organization_id)
            ->with(['owner', 'items', 'collaborators'])
            ->when($request->status, fn($q, $status) => $q->byStatus($status))
            ->when($request->health_status, fn($q, $health) => $q->byHealthStatus($health))
            ->when($request->search, function($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('budget_number', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => BudgetResource::collection($budgets),
            'meta' => [
                'current_page' => $budgets->currentPage(),
                'total' => $budgets->total(),
                'per_page' => $budgets->perPage(),
            ],
        ]);
    }

    public function store(CreateBudgetRequest $request): JsonResponse
    {
        $budget = $this->budgetService->createBudget(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Budget created successfully',
            'data' => new BudgetResource($budget),
        ], 201);
    }

    public function show(Budget $budget): JsonResponse
    {
        $this->authorize('view', $budget);

        $budget->load([
            'owner',
            'items.category',
            'collaborators',
            'transactions' => fn($q) => $q->latest()->limit(10),
            'insights' => fn($q) => $q->where('is_dismissed', false)->latest()->limit(5),
            'alerts' => fn($q) => $q->where('is_resolved', false)->latest(),
        ]);

        return response()->json([
            'success' => true,
            'data' => new BudgetResource($budget),
        ]);
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): JsonResponse
    {
        $this->authorize('update', $budget);

        $budget = $this->budgetService->updateBudget(
            $budget,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Budget updated successfully',
            'data' => new BudgetResource($budget),
        ]);
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $this->authorize('delete', $budget);

        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget deleted successfully',
        ]);
    }

    public function dashboard(Budget $budget): JsonResponse
    {
        $this->authorize('view', $budget);

        $dashboard = $this->budgetService->getDashboardData($budget);

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }

    public function submitForApproval(Budget $budget): JsonResponse
    {
        $this->authorize('update', $budget);

        try {
            $budget->submitForApproval();

            return response()->json([
                'success' => true,
                'message' => 'Budget submitted for approval',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function clone(Budget $budget): JsonResponse
    {
        $this->authorize('view', $budget);

        $newBudget = $this->budgetService->cloneBudget($budget);

        return response()->json([
            'success' => true,
            'message' => 'Budget cloned successfully',
            'data' => new BudgetResource($newBudget),
        ], 201);
    }

    public function varianceReport(Budget $budget): JsonResponse
    {
        $this->authorize('view', $budget);

        $report = $this->budgetService->generateVarianceReport($budget);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
```

### Request Validation

**File: app/Http/Requests/CreateBudgetRequest.php**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'period_type' => 'required|in:daily,weekly,monthly,quarterly,annual,custom',
            'total_amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'department' => 'nullable|string|max:100',
            'project_id' => 'nullable|string',
            'template_id' => 'nullable|exists:budget_templates,id',
            'allow_overspend' => 'boolean',
            'require_approval' => 'boolean',
            'alert_threshold' => 'integer|min:1|max:100',
            'tags' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            
            // Budget items
            'items' => 'nullable|array',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.category_id' => 'nullable|exists:budget_categories,id',
            'items.*.budgeted_amount' => 'required_with:items|numeric|min:0',
            'items.*.item_type' => 'in:expense,income',
            'items.*.frequency' => 'in:one_time,recurring_monthly,recurring_quarterly,recurring_annual',
        ];
    }
}
```

### Resource Transformers

**File: app/Http/Resources/BudgetResource.php**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'budget_number' => $this->budget_number,
            
            // Period
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'period_type' => $this->period_type,
            'days_remaining' => $this->days_remaining,
            
            // Amounts
            'currency_code' => $this->currency_code,
            'total_amount' => (float) $this->total_amount,
            'allocated_amount' => (float) $this->allocated_amount,
            'spent_amount' => (float) $this->spent_amount,
            'committed_amount' => (float) $this->committed_amount,
            'remaining_amount' => (float) $this->remaining_amount,
            
            // Status
            'status' => $this->status,
            'health_status' => $this->health_status,
            'utilization_percentage' => $this->utilization_percentage,
            
            // Metrics
            'daily_burn_rate' => (float) $this->daily_burn_rate,
            'projected_spend' => (float) $this->projected_spend,
            
            // Ownership
            'owner' => new UserResource($this->whenLoaded('owner')),
            'department' => $this->department,
            'project_id' => $this->project_id,
            
            // Settings
            'allow_overspend' => $this->allow_overspend,
            'require_approval' => $this->require_approval,
            'alert_threshold' => $this->alert_threshold,
            
            // Metadata
            'tags' => $this->tags,
            'custom_fields' => $this->custom_fields,
            'version' => $this->version,
            
            // Relationships
            'items' => BudgetItemResource::collection($this->whenLoaded('items')),
            'collaborators' => UserResource::collection($this->whenLoaded('collaborators')),
            'recent_transactions' => BudgetTransactionResource::collection($this->whenLoaded('transactions')),
            'active_insights' => BudgetInsightResource::collection($this->whenLoaded('insights')),
            'active_alerts' => BudgetAlertResource::collection($this->whenLoaded('alerts')),
            
            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

---

## AUTHENTICATION & AUTHORIZATION

### JWT Middleware

**File: app/Http/Middleware/JWTAuth.php**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use App\Models\Organization;

class JWTAuth
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided',
                ], 401);
            }

            // Decode token
            $payload = JWTAuth::setToken($token)->getPayload();
            
            // Extract user data from token
            $organizationData = $payload->get('organization');
            $userData = $payload->get('user');
            
            // Find or create organization
            $organization = Organization::firstOrCreate(
                [
                    'parent_app' => $organizationData['parent_app'],
                    'parent_id' => $organizationData['parent_id'],
                ],
                [
                    'name' => $organizationData['name'],
                    'type' => $organizationData['type'],
                    'currency_code' => $organizationData['currency_code'] ?? 'ZMW',
                    'settings' => $organizationData['settings'] ?? [],
                ]
            );
            
            // Find or create user
            $user = User::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'parent_user_id' => $userData['id'],
                ],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'role' => $userData['role'] ?? 'member',
                ]
            );
            
            // Attach to request
            $request->setUserResolver(fn() => $user);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 401);
        }

        return $next($request);
    }
}
```

### Policy Example

**File: app/Policies/BudgetPolicy.php**
```php
<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Budget $budget): bool
    {
        // Check if user belongs to same organization
        if ($user->organization_id !== $budget->organization_id) {
            return false;
        }

        // Owner can view
        if ($budget->owner_id === $user->id) {
            return true;
        }

        // Collaborators can view
        if ($budget->collaborators->contains($user->id)) {
            return true;
        }

        // Admin/Owner roles can view
        return in_array($user->role, ['admin', 'owner']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'owner', 'member']);
    }

    public function update(User $user, Budget $budget): bool
    {
        // Check organization
        if ($user->organization_id !== $budget->organization_id) {
            return false;
        }

        // Can't modify if in certain statuses
        if (in_array($budget->status, ['approved', 'active', 'closed'])) {
            return in_array($user->role, ['admin', 'owner']);
        }

        return $budget->canBeModifiedBy($user);
    }

    public function delete(User $user, Budget $budget): bool
    {
        if ($user->organization_id !== $budget->organization_id) {
            return false;
        }

        // Only owner or admins can delete
        return $budget->owner_id === $user->id || 
               in_array($user->role, ['admin', 'owner']);
    }
}
```

---

## BUSINESS LOGIC

### Budget Service

**File: app/Services/BudgetService.php**
```php
<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function createBudget(User $user, array $data): Budget
    {
        return DB::transaction(function () use ($user, $data) {
            // Create budget
            $budget = Budget::create([
                'organization_id' => $user->organization_id,
                'owner_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'period_type' => $data['period_type'],
                'total_amount' => $data['total_amount'],
                'currency_code' => $data['currency_code'],
                'department' => $data['department'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'template_id' => $data['template_id'] ?? null,
                'allow_overspend' => $data['allow_overspend'] ?? false,
                'require_approval' => $data['require_approval'] ?? true,
                'alert_threshold' => $data['alert_threshold'] ?? 80,
                'tags' => $data['tags'] ?? [],
                'custom_fields' => $data['custom_fields'] ?? [],
            ]);

            // Generate budget number
            $budget->update([
                'budget_number' => $budget->generateBudgetNumber(),
            ]);

            // Create budget items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    BudgetItem::create([
                        'budget_id' => $budget->id,
                        'category_id' => $itemData['category_id'] ?? null,
                        'name' => $itemData['name'],
                        'description' => $itemData['description'] ?? null,
                        'budgeted_amount' => $itemData['budgeted_amount'],
                        'item_type' => $itemData['item_type'] ?? 'expense',
                        'frequency' => $itemData['frequency'] ?? 'one_time',
                    ]);
                }

                $budget->recalculateAllocated();
            }

            // Log activity
            activity()
                ->performedOn($budget)
                ->causedBy($user)
                ->log('Budget created');

            return $budget->fresh(['items', 'owner']);
        });
    }

    public function updateBudget(Budget $budget, array $data): Budget
    {
        return DB::transaction(function () use ($budget, $data) {
            $budget->update($data);

            // Log activity
            activity()
                ->performedOn($budget)
                ->log('Budget updated');

            return $budget->fresh();
        });
    }

    public function cloneBudget(Budget $budget): Budget
    {
        return DB::transaction(function () use ($budget) {
            $newBudget = $budget->replicate();
            $newBudget->status = 'draft';
            $newBudget->version = 1;
            $newBudget->spent_amount = 0;
            $newBudget->committed_amount = 0;
            $newBudget->parent_budget_id = $budget->id;
            $newBudget->save();

            // Generate new budget number
            $newBudget->update([
                'budget_number' => $newBudget->generateBudgetNumber(),
            ]);

            // Clone items
            foreach ($budget->items as $item) {
                $newItem = $item->replicate();
                $newItem->budget_id = $newBudget->id;
                $newItem->spent_amount = 0;
                $newItem->committed_amount = 0;
                $newItem->save();
            }

            $newBudget->recalculateAllocated();

            return $newBudget->fresh(['items']);
        });
    }

    public function getDashboardData(Budget $budget): array
    {
        return [
            'overview' => [
                'total_amount' => $budget->total_amount,
                'spent_amount' => $budget->spent_amount,
                'remaining_amount' => $budget->remaining_amount,
                'utilization_percentage' => $budget->utilization_percentage,
                'health_status' => $budget->health_status,
                'days_remaining' => $budget->days_remaining,
            ],
            
            'metrics' => [
                'daily_burn_rate' => $budget->daily_burn_rate,
                'projected_spend' => $budget->projected_spend,
                'average_transaction' => $budget->transactions()->avg('amount') ?? 0,
                'transaction_count' => $budget->transactions()->count(),
            ],
            
            'top_categories' => $this->getTopCategories($budget, 5),
            'spending_trend' => $this->getSpendingTrend($budget),
            'item_breakdown' => $this->getItemBreakdown($budget),
            'recent_transactions' => $budget->transactions()
                ->with('category')
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    public function generateVarianceReport(Budget $budget): array
    {
        $items = $budget->items()->with('category')->get();

        return [
            'budget_id' => $budget->id,
            'budget_name' => $budget->name,
            'period' => [
                'start' => $budget->start_date->toDateString(),
                'end' => $budget->end_date->toDateString(),
            ],
            'summary' => [
                'total_budgeted' => $budget->total_amount,
                'total_spent' => $budget->spent_amount,
                'total_variance' => $budget->total_amount - $budget->spent_amount,
                'variance_percentage' => $budget->total_amount > 0 
                    ? round((($budget->total_amount - $budget->spent_amount) / $budget->total_amount) * 100, 2)
                    : 0,
            ],
            'items' => $items->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category->name ?? 'Uncategorized',
                'budgeted' => $item->budgeted_amount,
                'spent' => $item->spent_amount,
                'variance' => $item->budgeted_amount - $item->spent_amount,
                'variance_percentage' => $item->budgeted_amount > 0
                    ? round((($item->budgeted_amount - $item->spent_amount) / $item->budgeted_amount) * 100, 2)
                    : 0,
                'status' => $item->health_status,
            ]),
        ];
    }

    private function getTopCategories(Budget $budget, int $limit): array
    {
        return $budget->transactions()
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->with('category')
            ->get()
            ->map(fn($item) => [
                'category' => $item->category->name ?? 'Uncategorized',
                'amount' => $item->total,
                'color' => $item->category->color ?? '#14b8a6',
            ])
            ->toArray();
    }

    private function getSpendingTrend(Budget $budget): array
    {
        $startDate = $budget->start_date;
        $endDate = min($budget->end_date, now());
        
        $dailySpending = $budget->transactions()
            ->selectRaw('DATE(transaction_date) as date, SUM(amount) as total')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $cumulativeSpending = [];
        $runningTotal = 0;

        foreach ($dailySpending as $day) {
            $runningTotal += $day->total;
            $cumulativeSpending[] = [
                'date' => $day->date,
                'amount' => $runningTotal,
            ];
        }

        return $cumulativeSpending;
    }

    private function getItemBreakdown(Budget $budget): array
    {
        return $budget->items()
            ->with('category')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category->name ?? 'Uncategorized',
                'budgeted' => $item->budgeted_amount,
                'spent' => $item->spent_amount,
                'remaining' => $item->remaining_amount,
                'utilization' => $item->utilization_percentage,
                'health_status' => $item->health_status,
            ])
            ->toArray();
    }
}
```

---

## AI INTEGRATION

### AI Service

**File: app/Services/AIService.php**
```php
<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetInsight;
use App\Models\BudgetTransaction;
use App\Models\BudgetCategory;
use OpenAI\Laravel\Facades\OpenAI;

class AIService
{
    public function categorizeTransaction(BudgetTransaction $transaction): void
    {
        $categories = BudgetCategory::where('organization_id', $transaction->organization_id)
            ->where('is_active', true)
            ->get(['id', 'name', 'description'])
            ->toArray();

        $prompt = "Categorize this transaction:\n";
        $prompt .= "Description: {$transaction->description}\n";
        $prompt .= "Amount: {$transaction->amount}\n";
        $prompt .= "Date: {$transaction->transaction_date}\n\n";
        $prompt .= "Available categories:\n";
        
        foreach ($categories as $category) {
            $prompt .= "- {$category['name']} (ID: {$category['id']}): {$category['description']}\n";
        }
        
        $prompt .= "\nReturn only the category ID and confidence score (0-100) in JSON format: {\"category_id\": \"uuid\", \"confidence\": 95}";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a financial transaction categorization expert.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
        ]);

        $result = json_decode($response->choices[0]->message->content, true);

        $transaction->update([
            'category_id' => $result['category_id'],
            'is_auto_categorized' => true,
            'ai_confidence' => $result['confidence'],
        ]);
    }

    public function generateBudgetInsights(Budget $budget): void
    {
        $data = $this->prepareBudgetDataForAI($budget);

        $prompt = "Analyze this budget and provide actionable insights:\n\n";
        $prompt .= json_encode($data, JSON_PRETTY_PRINT);
        $prompt .= "\n\nProvide 3-5 key insights including:\n";
        $prompt .= "1. Spending patterns\n";
        $prompt .= "2. Areas of concern\n";
        $prompt .= "3. Optimization opportunities\n";
        $prompt .= "4. Forecasted issues\n";
        $prompt .= "\nReturn as JSON array: [{\"type\": \"trend_analysis\", \"severity\": \"warning\", \"title\": \"...\", \"description\": \"...\", \"recommendations\": []}]";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a financial analyst specializing in budget optimization.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.5,
        ]);

        $insights = json_decode($response->choices[0]->message->content, true);

        foreach ($insights as $insight) {
            BudgetInsight::create([
                'organization_id' => $budget->organization_id,
                'budget_id' => $budget->id,
                'insight_type' => $insight['type'],
                'severity' => $insight['severity'],
                'title' => $insight['title'],
                'description' => $insight['description'],
                'ai_model' => 'gpt-4',
                'confidence_score' => 85,
                'recommendations' => $insight['recommendations'] ?? [],
            ]);
        }
    }

    public function detectAnomalies(Budget $budget): array
    {
        $recentTransactions = $budget->transactions()
            ->latest()
            ->limit(50)
            ->get(['amount', 'description', 'transaction_date']);

        $prompt = "Analyze these recent transactions for anomalies:\n\n";
        $prompt .= json_encode($recentTransactions, JSON_PRETTY_PRINT);
        $prompt .= "\n\nIdentify:\n";
        $prompt .= "1. Unusual amounts\n";
        $prompt .= "2. Unexpected patterns\n";
        $prompt .= "3. Potential errors\n";
        $prompt .= "\nReturn as JSON array with anomalies found.";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a fraud detection and anomaly detection expert.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }

    private function prepareBudgetDataForAI(Budget $budget): array
    {
        return [
            'budget_info' => [
                'name' => $budget->name,
                'period' => [
                    'start' => $budget->start_date->toDateString(),
                    'end' => $budget->end_date->toDateString(),
                    'days_remaining' => $budget->days_remaining,
                ],
                'amounts' => [
                    'total' => $budget->total_amount,
                    'spent' => $budget->spent_amount,
                    'remaining' => $budget->remaining_amount,
                    'utilization_percentage' => $budget->utilization_percentage,
                ],
                'metrics' => [
                    'daily_burn_rate' => $budget->daily_burn_rate,
                    'projected_spend' => $budget->projected_spend,
                ],
            ],
            'items' => $budget->items->map(fn($item) => [
                'name' => $item->name,
                'budgeted' => $item->budgeted_amount,
                'spent' => $item->spent_amount,
                'utilization' => $item->utilization_percentage,
                'status' => $item->health_status,
            ]),
            'spending_by_category' => $budget->transactions()
                ->selectRaw('category_id, SUM(amount) as total')
                ->groupBy('category_id')
                ->with('category:id,name')
                ->get()
                ->mapWithKeys(fn($item) => [
                    $item->category->name ?? 'Uncategorized' => $item->total,
                ]),
        ];
    }
}
```

### OCR Service

**File: app/Services/OCRService.php**
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class OCRService
{
    public function extractReceiptData(UploadedFile $file): array
    {
        // Store file temporarily
        $path = $file->store('temp-receipts');
        $fullPath = Storage::path($path);

        // Use Tesseract OCR
        $text = shell_exec("tesseract {$fullPath} stdout");

        // Clean up
        Storage::delete($path);

        // Parse extracted text
        return $this->parseReceiptText($text);
    }

    private function parseReceiptText(string $text): array
    {
        // Use GPT-4 to structure the data
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Extract structured data from receipt text.'],
                ['role' => 'user', 'content' => "Extract date, amount, merchant, and items from:\n\n{$text}\n\nReturn JSON: {\"date\": \"YYYY-MM-DD\", \"amount\": 0.00, \"merchant\": \"\", \"items\": []}"],
            ],
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }

    public function processStatementFile(UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension();

        return match($extension) {
            'csv' => $this->parseCSVStatement($file),
            'pdf' => $this->parsePDFStatement($file),
            'xlsx', 'xls' => $this->parseExcelStatement($file),
            default => throw new \Exception('Unsupported file format'),
        };
    }

    private function parseCSVStatement(UploadedFile $file): array
    {
        $transactions = [];
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip header
        fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            $transactions[] = [
                'date' => $row[0],
                'description' => $row[1],
                'amount' => floatval($row[2]),
            ];
        }
        
        fclose($handle);
        
        return $transactions;
    }

    private function parsePDFStatement(UploadedFile $file): array
    {
        // Use AWS Textract or similar service
        // For now, return placeholder
        return [];
    }

    private function parseExcelStatement(UploadedFile $file): array
    {
        // Use PhpSpreadsheet
        // For now, return placeholder
        return [];
    }
}
```

---

## FRONTEND COMPONENTS

### Dashboard Card Component (React)

**File: BudgetDashboardCard.jsx**
```jsx
import React from 'react';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import StatValue from '@/Components/ui/StatValue';
import { TrendingUp, TrendingDown, AlertTriangle } from 'lucide-react';

const BudgetDashboardCard = ({ 
  title, 
  value, 
  subtitle, 
  trend, 
  status = 'healthy',
  icon: Icon,
  onClick 
}) => {
  const statusColors = {
    healthy: 'text-green-600 bg-green-50',
    warning: 'text-amber-600 bg-amber-50',
    danger: 'text-red-600 bg-red-50',
    overspent: 'text-red-700 bg-red-100',
  };

  const TrendIcon = trend >= 0 ? TrendingUp : TrendingDown;
  the trendColor = trend >= 0 ? 'text-green-600' : 'text-red-600';

  return (
    <Card 
      variant="glass" 
      className={`relative overflow-hidden cursor-pointer hover:shadow-lg transition-all ${onClick ? 'hover:scale-105' : ''}`}
      onClick={onClick}
    >
      <div className={`absolute top-0 right-0 w-32 h-32 opacity-10 ${statusColors[status]}`}>
        {Icon && <Icon className="w-full h-full" />}
      </div>
      
      <CardContent className="relative z-10 pt-6">
        <div className="flex items-start justify-between mb-4">
          <div className="flex-1">
            <p className="text-sm font-medium text-gray-500 mb-2">{title}</p>
            <StatValue value={value} size="2xl" className="text-gray-900" />
          </div>
          {Icon && (
            <div className={`p-3 rounded-xl ${statusColors[status]}`}>
              <Icon className="h-6 w-6" strokeWidth={1.5} />
            </div>
          )}
        </div>
        
        <div className="flex items-center justify-between">
          <p className="text-sm text-gray-600">{subtitle}</p>
          {typeof trend === 'number' && (
            <div className={`flex items-center text-sm font-semibold ${trendColor}`}>
              <TrendIcon className="h-4 w-4 mr-1" />
              {Math.abs(trend)}%
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
};

export default BudgetDashboardCard;
```

### Budget List Component

**File: BudgetList.jsx**
```jsx
import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import BudgetDashboardCard from './BudgetDashboardCard';
import { DollarSign, TrendingUp, AlertCircle, CheckCircle } from 'lucide-react';

const BudgetList = ({ organizationId }) => {
  const [budgets, setBudgets] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchBudgets();
    fetchStats();
  }, [organizationId]);

  const fetchBudgets = async () => {
    try {
      const response = await axios.get('/api/v1/budgets', {
        params: { organization_id: organizationId },
      });
      setBudgets(response.data.data);
    } catch (error) {
      console.error('Error fetching budgets:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await axios.get(`/api/v1/organizations/${organizationId}/stats`);
      setStats(response.data.data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  if (loading) {
    return <div>Loading...</div>;
  }

  return (
    <div className="space-y-8">
      {/* Stats Grid */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <BudgetDashboardCard
            title="Total Budgets"
            value={stats.total_budgets}
            subtitle={`${stats.active_budgets} active`}
            icon={DollarSign}
            status="healthy"
          />
          
          <BudgetDashboardCard
            title="Total Allocated"
            value={stats.total_allocated}
            subtitle="Across all budgets"
            icon={TrendingUp}
            status="healthy"
            trend={stats.allocation_trend}
          />
          
          <BudgetDashboardCard
            title="At Risk"
            value={stats.at_risk_budgets}
            subtitle="Above 80% utilization"
            icon={AlertCircle}
            status="warning"
          />
          
          <BudgetDashboardCard
            title="On Track"
            value={stats.healthy_budgets}
            subtitle="Below 80% utilization"
            icon={CheckCircle}
            status="healthy"
          />
        </div>
      )}

      {/* Budget Cards */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {budgets.map((budget) => (
          <Link key={budget.id} href={`/budgets/${budget.id}`}>
            <Card variant="glass" className="hover:shadow-lg transition-shadow">
              <CardHeader className="flex items-start justify-between">
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">{budget.name}</h3>
                  <p className="text-sm text-gray-500">{budget.budget_number}</p>
                </div>
                <Badge 
                  variant={budget.health_status === 'healthy' ? 'success' : 'warning'}
                >
                  {budget.health_status}
                </Badge>
              </CardHeader>
              
              <CardContent>
                <div className="space-y-4">
                  {/* Progress Bar */}
                  <div>
                    <div className="flex justify-between text-sm mb-2">
                      <span className="text-gray-600">Utilization</span>
                      <span className="font-semibold">{budget.utilization_percentage}%</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        className={`h-2 rounded-full transition-all ${
                          budget.health_status === 'healthy' ? 'bg-teal-500' :
                          budget.health_status === 'warning' ? 'bg-amber-500' :
                          'bg-red-500'
                        }`}
                        style={{ width: `${Math.min(budget.utilization_percentage, 100)}%` }}
                      />
                    </div>
                  </div>

                  {/* Amounts */}
                  <div className="grid grid-cols-3 gap-4 pt-4 border-t">
                    <div>
                      <p className="text-xs text-gray-500">Total</p>
                      <p className="text-sm font-semibold">{formatCurrency(budget.total_amount)}</p>
                    </div>
                    <div>
                      <p className="text-xs text-gray-500">Spent</p>
                      <p className="text-sm font-semibold">{formatCurrency(budget.spent_amount)}</p>
                    </div>
                    <div>
                      <p className="text-xs text-gray-500">Remaining</p>
                      <p className="text-sm font-semibold">{formatCurrency(budget.remaining_amount)}</p>
                    </div>
                  </div>

                  {/* Period */}
                  <div className="flex items-center justify-between text-xs text-gray-500 pt-2">
                    <span>{budget.start_date} - {budget.end_date}</span>
                    <span>{budget.days_remaining} days left</span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-ZM', {
    style: 'currency',
    currency: 'ZMW',
  }).format(amount);
};

export default BudgetList;
```

---

## DEPLOYMENT GUIDE

### Server Requirements
- **OS:** Ubuntu 22.04 LTS
- **PHP:** 8.2+
- **PostgreSQL:** 16+
- **Redis:** 7+
- **Nginx:** Latest
- **Node.js:** 20+ (for build tools)

### Environment Setup

**File: .env.production**
```env
APP_NAME="Penda Budget Service"
APP_ENV=production
APP_KEY=base64:generated_key
APP_DEBUG=false
APP_URL=https://budgets.penda.digital

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=penda_budgets
DB_USERNAME=penda_user
DB_PASSWORD=secure_password

REDIS_HOST=your-redis-host
REDIS_PASSWORD=redis_password
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=penda-budgets

OPENAI_API_KEY=your_openai_key

MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@penda.digital
MAIL_FROM_NAME="Penda Budget Service"

AFRICAS_TALKING_USERNAME=your_username
AFRICAS_TALKING_API_KEY=your_api_key

WHATSAPP_PHONE_NUMBER_ID=your_phone_id
WHATSAPP_ACCESS_TOKEN=your_token
```

### Nginx Configuration

**File: /etc/nginx/sites-available/budgets.penda.digital**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name budgets.penda.digital;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name budgets.penda.digital;

    root /var/www/penda-budget-service/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/budgets.penda.digital/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/budgets.penda.digital/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Deployment Script

**File: deploy.sh**
```bash
#!/bin/bash

echo "🚀 Deploying Penda Budget Service..."

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart

# Restart PHP-FPM
sudo systemctl reload php8.2-fpm

# Restart Nginx
sudo systemctl reload nginx

echo "✅ Deployment complete!"
```

### Supervisor Configuration

**File: /etc/supervisor/conf.d/penda-budget-worker.conf**
```ini
[program:penda-budget-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/penda-budget-service/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/penda-budget-service/storage/logs/worker.log
stopwaitsecs=3600
```

---

## TESTING STRATEGY

### Feature Test Example

**File: tests/Feature/BudgetTest.php**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Budget;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_budget()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/budgets', [
                'name' => 'Q1 Budget',
                'start_date' => '2024-01-01',
                'end_date' => '2024-03-31',
                'period_type' => 'quarterly',
                'total_amount' => 50000,
                'currency_code' => 'ZMW',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'budget_number'],
            ]);

        $this->assertDatabaseHas('budgets', [
            'name' => 'Q1 Budget',
            'organization_id' => $org->id,
        ]);
    }

    public function test_budget_calculates_utilization_correctly()
    {
        $budget = Budget::factory()->create([
            'total_amount' => 10000,
            'spent_amount' => 8000,
        ]);

        $this->assertEquals(80, $budget->utilization_percentage);
        $this->assertEquals(2000, $budget->remaining_amount);
    }

    public function test_budget_triggers_alert_at_threshold()
    {
        $budget = Budget::factory()->create([
            'total_amount' => 10000,
            'spent_amount' => 8000,
            'alert_threshold' => 80,
        ]);

        $budget->checkAlerts();

        $this->assertDatabaseHas('budget_alerts', [
            'budget_id' => $budget->id,
            'alert_type' => 'threshold_reached',
        ]);
    }
}
```

### Unit Test Example

**File: tests/Unit/BudgetServiceTest.php**
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BudgetService;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetService::class);
    }

    public function test_creates_budget_with_items()
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'Test Budget',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'period_type' => 'annual',
            'total_amount' => 100000,
            'currency_code' => 'ZMW',
            'items' => [
                [
                    'name' => 'Marketing',
                    'budgeted_amount' => 30000,
                ],
                [
                    'name' => 'Operations',
                    'budgeted_amount' => 70000,
                ],
            ],
        ];

        $budget = $this->service->createBudget($user, $data);

        $this->assertInstanceOf(Budget::class, $budget);
        $this->assertCount(2, $budget->items);
        $this->assertEquals(100000, $budget->allocated_amount);
    }

    public function test_clones_budget_correctly()
    {
        $original = Budget::factory()
            ->hasItems(3)
            ->create();

        $cloned = $this->service->cloneBudget($original);

        $this->assertNotEquals($original->id, $cloned->id);
        $this->assertEquals($original->items->count(), $cloned->items->count());
        $this->assertEquals('draft', $cloned->status);
        $this->assertEquals(0, $cloned->spent_amount);
    }
}
```

---

## POSTMAN COLLECTION

**File: postman_collection.json**
```json
{
  "info": {
    "name": "Penda Budget Service API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Authentication",
      "item": [
        {
          "name": "Verify Token",
          "request": {
            "method": "POST",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{token}}"
              }
            ],
            "url": {
              "raw": "{{base_url}}/api/v1/auth/verify-token",
              "host": ["{{base_url}}"],
              "path": ["api", "v1", "auth", "verify-token"]
            }
          }
        }
      ]
    },
    {
      "name": "Budgets",
      "item": [
        {
          "name": "List Budgets",
          "request": {
            "method": "GET",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{token}}"
              }
            ],
            "url": {
              "raw": "{{base_url}}/api/v1/budgets?status=active",
              "host": ["{{base_url}}"],
              "path": ["api", "v1", "budgets"],
              "query": [
                {
                  "key": "status",
                  "value": "active"
                }
              ]
            }
          }
        },
        {
          "name": "Create Budget",
          "request": {
            "method": "POST",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{token}}"
              },
              {
                "key": "Content-Type",
                "value": "application/json"
              }
            ],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"name\": \"Q1 2024 Budget\",\n  \"start_date\": \"2024-01-01\",\n  \"end_date\": \"2024-03-31\",\n  \"period_type\": \"quarterly\",\n  \"total_amount\": 50000,\n  \"currency_code\": \"ZMW\",\n  \"items\": [\n    {\n      \"name\": \"Marketing\",\n      \"budgeted_amount\": 20000\n    }\n  ]\n}"
            },
            "url": {
              "raw": "{{base_url}}/api/v1/budgets",
              "host": ["{{base_url}}"],
              "path": ["api", "v1", "budgets"]
            }
          }
        }
      ]
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "https://budgets.penda.digital"
    },
    {
      "key": "token",
      "value": "your_jwt_token_here"
    }
  ]
}
```

---

## NEXT STEPS

### Phase 1: Setup (Week 1)
1. ✅ Initialize Laravel project
2. ✅ Set up PostgreSQL database
3. ✅ Run migrations
4. ✅ Configure JWT authentication
5. ✅ Set up Redis & queues

### Phase 2: Core Development (Weeks 2-3)
1. ✅ Build budget CRUD operations
2. ✅ Implement approval workflows
3. ✅ Build transaction tracking
4. ✅ Real-time reconciliation

### Phase 3: AI Integration (Week 4)
1. ✅ OpenAI integration
2. ✅ Auto-categorization
3. ✅ Insights generation
4. ✅ Anomaly detection

### Phase 4: Testing & Documentation (Week 5)
1. ✅ Write unit tests
2. ✅ Write feature tests
3. ✅ API documentation
4. ✅ User guides

### Phase 5: Deployment (Week 6)
1. ✅ VPS setup
2. ✅ SSL configuration
3. ✅ CI/CD pipeline
4. ✅ Monitoring setup

---

## CONCLUSION

This comprehensive implementation guide provides everything needed to build V1 of the Penda Budget Service. The microservice architecture ensures scalability, and the AI-powered features make it the best budget management tool for African businesses.

**Key Highlights:**
- ✅ Flexible budget periods (1 day to multi-year)
- ✅ Real-time tracking and reconciliation
- ✅ AI-powered categorization and insights
- ✅ Multi-level approval workflows
- ✅ Beautiful dashboard with cards
- ✅ Ready for Addy Business, Projjo, and LiDe

🔥 **Let's build the future of budget management!** 🔥
