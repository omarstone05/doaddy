<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulting_deliverables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('milestone_id')->nullable();
            
            // Deliverable details
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->nullable(); // report, design, code, document, presentation, etc.
            $table->integer('order')->default(0);
            
            // Status
            $table->string('status')->default('draft'); // draft, review, revision, approved, rejected
            $table->integer('version')->default(1);
            
            // Dates
            $table->date('due_date')->nullable();
            $table->date('submitted_at')->nullable();
            $table->date('approved_at')->nullable();
            
            // Assignment
            $table->uuid('owner_id')->nullable();
            $table->json('contributors')->nullable();
            
            // Approval
            $table->uuid('approved_by')->nullable();
            $table->text('approval_comments')->nullable();
            $table->json('approval_history')->nullable();
            
            // Files
            $table->json('files')->nullable(); // Array of file paths/URLs
            $table->json('version_history')->nullable();
            
            // Client visibility
            $table->boolean('visible_to_client')->default(true);
            $table->boolean('requires_client_approval')->default(true);
            
            // Comments thread
            $table->json('comments')->nullable();
            
            // Metadata
            $table->json('custom_fields')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['project_id', 'status']);
            $table->index('due_date');
            $table->index('owner_id');
            
            // Foreign keys
            $table->foreign('project_id')
                  ->references('id')
                  ->on('consulting_projects')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_deliverables');
    }
};

