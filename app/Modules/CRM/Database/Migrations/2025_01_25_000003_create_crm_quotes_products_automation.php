<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products Catalog
        Schema::create('crm_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Product Details
            $table->string('product_code')->unique();
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->text('long_description')->nullable();
            
            // Category
            $table->string('product_category')->nullable();
            $table->string('product_family')->nullable();
            
            // Pricing
            $table->decimal('unit_price', 15, 2);
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->string('currency', 3)->default('ZMW');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_period', ['monthly', 'quarterly', 'annual'])->nullable();
            
            // Stock (if physical product)
            $table->boolean('track_inventory')->default(false);
            $table->decimal('current_stock', 15, 3)->nullable();
            $table->decimal('low_stock_threshold', 15, 3)->nullable();
            
            // Tax
            $table->boolean('is_taxable')->default(true);
            $table->decimal('tax_rate', 8, 2)->default(16);
            
            // Availability
            $table->boolean('is_active')->default(true);
            $table->date('available_from')->nullable();
            $table->date('available_until')->nullable();
            
            // Metadata
            $table->string('image_url')->nullable();
            $table->json('images')->nullable();
            $table->json('specifications')->nullable();
            $table->json('tags')->nullable();
            
            // Sales Info
            $table->decimal('average_deal_size', 15, 2)->nullable();
            $table->integer('total_sold_quantity')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'is_active']);
            $table->index('product_code');
            $table->index('product_category');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Quotes
        Schema::create('crm_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Quote Details
            $table->string('quote_number')->unique();
            $table->string('quote_name');
            $table->integer('version')->default(1);
            $table->uuid('parent_quote_id')->nullable();
            
            // Related To
            $table->uuid('contact_id');
            $table->uuid('opportunity_id')->nullable();
            
            // Amounts
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 8, 2)->default(0);
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('ZMW');
            
            // Dates
            $table->date('quote_date');
            $table->date('expiry_date');
            $table->date('sent_date')->nullable();
            $table->date('accepted_date')->nullable();
            $table->date('declined_date')->nullable();
            
            // Status
            $table->enum('status', [
                'draft', 'sent', 'viewed', 'accepted', 'declined', 'expired', 'revised'
            ])->default('draft');
            $table->boolean('is_primary')->default(true);
            
            // Terms
            $table->string('payment_terms')->nullable();
            $table->string('delivery_terms')->nullable();
            $table->integer('validity_period_days')->default(30);
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            
            // Owner
            $table->uuid('owner_id');
            $table->uuid('prepared_by');
            $table->uuid('approved_by')->nullable();
            
            // Conversion
            $table->boolean('converted_to_invoice')->default(false);
            $table->uuid('invoice_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            
            // Template
            $table->uuid('template_id')->nullable();
            
            // Signature
            $table->boolean('requires_signature')->default(false);
            $table->boolean('signed')->default(false);
            $table->text('signature_data')->nullable();
            $table->string('signed_by_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_ip')->nullable();
            
            // Tracking
            $table->integer('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            
            // Metadata
            $table->json('custom_fields')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index('contact_id');
            $table->index('opportunity_id');
            $table->index('quote_number');
            $table->index('expiry_date');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
                  
            $table->foreign('contact_id')
                  ->references('id')
                  ->on('crm_contacts')
                  ->onDelete('cascade');
        });

        // Quote Items
        Schema::create('crm_quote_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quote_id');
            $table->uuid('product_id')->nullable();
            
            // Product Details
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->string('product_code')->nullable();
            
            // Quantity & Pricing
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percentage', 8, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(16);
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('total', 15, 2);
            
            // Options
            $table->boolean('optional')->default(false);
            $table->boolean('optional_selected')->default(false);
            
            // Metadata
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('quote_id');
            $table->index('product_id');
            
            $table->foreign('quote_id')
                  ->references('id')
                  ->on('crm_quotes')
                  ->onDelete('cascade');
        });

        // Pipelines
        Schema::create('crm_pipelines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Pipeline Details
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'is_default']);
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Pipeline Stages
        Schema::create('crm_pipeline_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pipeline_id');
            
            // Stage Details
            $table->string('stage_name');
            $table->text('description')->nullable();
            $table->integer('stage_order');
            $table->integer('probability')->default(0);
            $table->boolean('is_closed_won')->default(false);
            $table->boolean('is_closed_lost')->default(false);
            
            // Stage Type
            $table->enum('stage_type', ['open', 'won', 'lost'])->default('open');
            
            // Automation
            $table->integer('days_until_stale')->nullable();
            $table->json('required_fields')->nullable();
            
            // Color
            $table->string('color')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['pipeline_id', 'stage_order']);
            
            $table->foreign('pipeline_id')
                  ->references('id')
                  ->on('crm_pipelines')
                  ->onDelete('cascade');
        });

        // Workflows
        Schema::create('crm_workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Workflow Details
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('workflow_type', [
                'lead_routing', 'opportunity_stage', 'activity_reminder',
                'email_sequence', 'task_creation', 'field_update'
            ]);
            
            // Trigger
            $table->enum('trigger_type', [
                'record_created', 'field_updated', 'stage_changed',
                'date_reached', 'manual', 'time_based'
            ]);
            $table->string('trigger_object'); // lead, contact, opportunity, activity
            $table->json('trigger_conditions'); // conditions to check
            
            // Actions
            $table->json('actions'); // array of actions to perform
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Execution
            $table->integer('execution_order')->default(0);
            $table->boolean('run_once')->default(false);
            $table->timestamp('last_executed_at')->nullable();
            $table->integer('execution_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'is_active']);
            $table->index('trigger_object');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Workflow Executions
        Schema::create('crm_workflow_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->uuid('organization_id');
            
            // Execution Details
            $table->string('record_type');
            $table->uuid('record_id');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            
            // Actions Performed
            $table->json('actions_performed')->nullable();
            
            // Timing
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_ms')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('workflow_id');
            $table->index(['record_type', 'record_id']);
            
            $table->foreign('workflow_id')
                  ->references('id')
                  ->on('crm_workflows')
                  ->onDelete('cascade');
        });

        // Tags
        Schema::create('crm_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Tag Details
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('tag_category')->nullable();
            
            // Usage
            $table->integer('usage_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('organization_id');
            $table->unique(['organization_id', 'name']);
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Taggables (Polymorphic)
        Schema::create('crm_taggables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tag_id');
            $table->string('taggable_type'); // lead, contact, opportunity
            $table->uuid('taggable_id');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['taggable_type', 'taggable_id']);
            $table->index('tag_id');
            
            $table->foreign('tag_id')
                  ->references('id')
                  ->on('crm_tags')
                  ->onDelete('cascade');
        });

        // Sales Teams
        Schema::create('crm_sales_teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Team Details
            $table->string('name');
            $table->text('description')->nullable();
            
            // Team Lead
            $table->uuid('team_lead_id')->nullable();
            
            // Members
            $table->json('members'); // array of user IDs
            
            // Goals
            $table->decimal('monthly_target', 15, 2)->nullable();
            $table->decimal('quarterly_target', 15, 2)->nullable();
            $table->decimal('annual_target', 15, 2)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('organization_id');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Territories
        Schema::create('crm_territories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Territory Details
            $table->string('name');
            $table->text('description')->nullable();
            
            // Geographic
            $table->json('countries')->nullable();
            $table->json('provinces')->nullable();
            $table->json('cities')->nullable();
            
            // Assignment
            $table->uuid('assigned_to')->nullable();
            $table->uuid('sales_team_id')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('organization_id');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Email Templates
        Schema::create('crm_email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Template Details
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('subject');
            $table->text('body_html');
            $table->text('body_text')->nullable();
            
            // Variables
            $table->json('available_variables')->nullable();
            
            // Usage
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('organization_id');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_email_templates');
        Schema::dropIfExists('crm_territories');
        Schema::dropIfExists('crm_sales_teams');
        Schema::dropIfExists('crm_taggables');
        Schema::dropIfExists('crm_tags');
        Schema::dropIfExists('crm_workflow_executions');
        Schema::dropIfExists('crm_workflows');
        Schema::dropIfExists('crm_pipeline_stages');
        Schema::dropIfExists('crm_pipelines');
        Schema::dropIfExists('crm_quote_items');
        Schema::dropIfExists('crm_quotes');
        Schema::dropIfExists('crm_products');
    }
};
