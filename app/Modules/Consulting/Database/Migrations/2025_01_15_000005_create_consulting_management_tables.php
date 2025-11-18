<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change Orders / Variations
        Schema::create('consulting_change_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            
            // Change order details
            $table->string('number')->unique(); // CO-001, CO-002, etc.
            $table->string('title');
            $table->text('description');
            $table->text('reason')->nullable();
            
            // Impact
            $table->decimal('cost_impact', 15, 2)->default(0);
            $table->integer('timeline_impact_days')->default(0);
            $table->text('scope_impact')->nullable();
            
            // Approval workflow
            $table->string('status')->default('draft'); // draft, pending_internal, pending_client, approved, rejected
            
            // Internal approval
            $table->uuid('requested_by');
            $table->uuid('internal_approved_by')->nullable();
            $table->timestamp('internal_approved_at')->nullable();
            $table->text('internal_comments')->nullable();
            
            // Client approval
            $table->uuid('client_approved_by')->nullable();
            $table->timestamp('client_approved_at')->nullable();
            $table->text('client_comments')->nullable();
            
            // Version control
            $table->integer('version')->default(1);
            $table->json('version_history')->nullable();
            
            // Files
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['project_id', 'status']);
            $table->index('number');
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });

        // Risk Log
        Schema::create('consulting_risks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            
            // Risk details
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable(); // budget, timeline, scope, technical, external
            
            // Assessment
            $table->string('probability')->default('medium'); // low, medium, high
            $table->string('impact')->default('medium'); // low, medium, high
            $table->integer('risk_score')->nullable(); // Calculated: probability × impact
            
            // Management
            $table->uuid('owner_id')->nullable(); // Person responsible
            $table->text('mitigation_plan')->nullable();
            $table->string('status')->default('identified'); // identified, monitoring, mitigating, realized, closed
            
            // Dates
            $table->date('identified_date');
            $table->date('review_date')->nullable();
            $table->date('closed_date')->nullable();
            
            // Metadata
            $table->json('custom_fields')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['project_id', 'status']);
            $table->index('risk_score');
            $table->index('owner_id');
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });

        // Issues Log
        Schema::create('consulting_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('task_id')->nullable();
            
            // Issue details
            $table->string('title');
            $table->text('description');
            $table->string('type')->nullable(); // bug, blocker, concern, request
            
            // Priority & Severity
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            
            // Assignment
            $table->uuid('reported_by');
            $table->uuid('assigned_to')->nullable();
            
            // Status
            $table->string('status')->default('open'); // open, investigating, in_progress, resolved, closed
            
            // Resolution
            $table->text('resolution_notes')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamp('resolved_at')->nullable();
            
            // Impact
            $table->boolean('blocks_progress')->default(false);
            $table->json('affected_tasks')->nullable();
            
            // Metadata
            $table->json('attachments')->nullable();
            $table->json('comments')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['project_id', 'status']);
            $table->index(['severity', 'status']);
            $table->index('assigned_to');
            $table->index('deadline');
            
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_issues');
        Schema::dropIfExists('consulting_risks');
        Schema::dropIfExists('consulting_change_orders');
    }
};

