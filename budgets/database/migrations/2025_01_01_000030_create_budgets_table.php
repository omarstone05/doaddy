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
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('budget_number', 50)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'annual', 'custom'])->default('custom');
            $table->string('currency_code', 3);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'active', 'closed', 'cancelled'])->default('draft');
            $table->enum('health_status', ['healthy', 'warning', 'danger', 'overspent'])->default('healthy');
            $table->foreignUuid('owner_id')->constrained('users');
            $table->string('department', 100)->nullable();
            $table->string('project_id')->nullable()->index();
            $table->foreignUuid('template_id')->nullable()->constrained('budget_templates');
            $table->foreignUuid('parent_budget_id')->nullable()->constrained('budgets');
            $table->integer('version')->default(1);
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
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
