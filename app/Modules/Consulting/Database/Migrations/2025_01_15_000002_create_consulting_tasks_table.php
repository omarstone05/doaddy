<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulting_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('parent_task_id')->nullable(); // For subtasks
            $table->uuid('milestone_id')->nullable();
            
            // Task details
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            
            // Assignment
            $table->uuid('assigned_to')->nullable();
            $table->json('assigned_team')->nullable(); // Multiple assignees
            
            // Status & Progress
            $table->string('status')->default('pending'); // pending, in_progress, review, blocked, completed
            $table->integer('progress_percentage')->default(0);
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            
            // Dates
            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            
            // Time tracking
            $table->integer('estimated_hours')->nullable();
            $table->integer('actual_hours')->default(0);
            $table->boolean('billable')->default(true);
            
            // Visibility
            $table->boolean('visible_to_client')->default(false);
            
            // Checklist
            $table->json('checklist')->nullable(); // Array of checklist items
            
            // Metadata
            $table->json('tags')->nullable();
            $table->json('attachments')->nullable();
            $table->json('custom_fields')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['project_id', 'status']);
            $table->index('assigned_to');
            $table->index('due_date');
            $table->index('parent_task_id');
            
            // Foreign keys
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });

        // Task dependencies
        Schema::create('consulting_task_dependencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->uuid('depends_on_task_id');
            $table->string('dependency_type')->default('finish_to_start'); // finish_to_start, start_to_start, finish_to_finish
            $table->integer('lag_days')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('task_id');
            $table->index('depends_on_task_id');
            
            // Foreign keys
            $table->foreign('task_id')
                  ->references('id')
                  ->on('consulting_tasks')
                  ->onDelete('cascade');
                  
            $table->foreign('depends_on_task_id')
                  ->references('id')
                  ->on('consulting_tasks')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_task_dependencies');
        Schema::dropIfExists('consulting_tasks');
    }
};

