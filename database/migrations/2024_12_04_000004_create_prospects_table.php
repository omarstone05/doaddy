<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prospects')) {
            // Table exists, add missing columns if any
            return;
        }

        Schema::create('prospects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->uuid('assigned_to')->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->string('prospect_code')->unique();
            
            // Basic Information
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            
            // Lead Source
            $table->enum('source', ['website', 'referral', 'cold_call', 'social_media', 'event', 'advertisement', 'other'])->nullable();
            $table->string('source_details')->nullable();
            
            // Pipeline Stage
            $table->enum('stage', ['lead', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'])->default('lead');
            $table->integer('stage_order')->default(1);
            $table->date('stage_changed_at')->nullable();
            
            // Opportunity
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->integer('probability')->default(0); // 0-100
            $table->date('expected_close_date')->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Contact Information
            $table->string('contact_person')->nullable();
            $table->string('contact_title')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            
            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            
            // Qualification
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->text('needs')->nullable();
            $table->text('pain_points')->nullable();
            $table->enum('budget_status', ['unknown', 'no_budget', 'has_budget', 'approved'])->default('unknown');
            $table->enum('decision_timeframe', ['immediate', 'this_month', 'this_quarter', 'this_year', 'unknown'])->default('unknown');
            
            // Engagement
            $table->integer('engagement_score')->default(0);
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('touch_count')->default(0);
            
            // Conversion
            $table->uuid('converted_to_customer_id')->nullable();
            $table->foreign('converted_to_customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->text('lost_reason')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'stage']);
            $table->index(['prospect_code']);
            $table->index(['email']);
            $table->index(['assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
