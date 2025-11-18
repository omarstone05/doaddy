<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulting_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Identity
            $table->string('name');
            $table->string('code')->nullable()->unique(); // Project code/number
            $table->string('type')->default('consulting'); // campaign, build, audit, event, consulting
            $table->text('description')->nullable();
            $table->string('status')->default('proposed'); // proposed, active, paused, complete, cancelled
            
            // Client
            $table->uuid('client_id')->nullable(); // Links to clients/customers table
            $table->string('client_name')->nullable(); // Cached for quick access
            
            // Team
            $table->uuid('project_manager_id')->nullable();
            $table->uuid('lead_id')->nullable();
            $table->json('team_members')->nullable(); // Array of user IDs
            $table->json('client_contacts')->nullable(); // Array of contact details
            
            // Financial
            $table->decimal('budget_total', 15, 2)->default(0);
            $table->json('budget_breakdown')->nullable(); // labour, materials, design, ads, subs
            $table->string('billing_model')->default('fixed'); // fixed, time_materials, milestone, retainer
            $table->decimal('rate_per_hour', 10, 2)->nullable();
            
            // Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            
            // Progress
            $table->integer('progress_percentage')->default(0);
            $table->string('health_status')->default('on_track'); // on_track, at_risk, delayed
            
            // Visibility & Access
            $table->boolean('client_portal_enabled')->default(true);
            $table->json('internal_access_rules')->nullable();
            $table->json('client_visibility_rules')->nullable();
            
            // Metadata
            $table->json('custom_fields')->nullable();
            $table->json('tags')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            
            // Soft deletes
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index('client_id');
            $table->index('project_manager_id');
            $table->index(['start_date', 'end_date']);
            
            // Foreign keys
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_projects');
    }
};

