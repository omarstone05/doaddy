<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Milestones
        Schema::create('consulting_milestones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            
            // Dates
            $table->date('due_date');
            $table->date('completed_at')->nullable();
            
            // Financial (if milestone-based billing)
            $table->decimal('payment_amount', 15, 2)->nullable();
            $table->boolean('payment_released')->default(false);
            
            // Status
            $table->string('status')->default('pending'); // pending, in_progress, completed, delayed
            
            // Dependencies
            $table->uuid('depends_on_milestone_id')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['project_id', 'status']);
            $table->index('due_date');
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });

        // Project Expenses
        Schema::create('consulting_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('task_id')->nullable();
            
            // Expense details
            $table->string('description');
            $table->string('category'); // materials, labour, design, ads, travel, etc.
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('ZMW');
            
            // Date
            $table->date('expense_date');
            
            // Supplier/Vendor
            $table->uuid('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            
            // Receipt
            $table->string('receipt_file')->nullable();
            $table->string('receipt_number')->nullable();
            
            // Approval
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Billing
            $table->boolean('billable_to_client')->default(false);
            $table->boolean('billed')->default(false);
            $table->decimal('markup_percentage', 5, 2)->default(0);
            
            // Payment
            $table->boolean('paid')->default(false);
            $table->date('paid_at')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['project_id', 'category']);
            $table->index('expense_date');
            $table->index('approval_status');
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });

        // Time Tracking
        Schema::create('consulting_time_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('task_id')->nullable();
            $table->uuid('user_id');
            
            // Time details
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('minutes'); // Total minutes worked
            $table->text('description')->nullable();
            
            // Billing
            $table->boolean('billable')->default(true);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->boolean('billed')->default(false);
            
            // Approval
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Timer tracking (for start/stop functionality)
            $table->boolean('is_running')->default(false);
            $table->timestamp('timer_started_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['project_id', 'user_id', 'date']);
            $table->index(['user_id', 'is_running']);
            $table->index('approval_status');
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_time_entries');
        Schema::dropIfExists('consulting_expenses');
        Schema::dropIfExists('consulting_milestones');
    }
};

