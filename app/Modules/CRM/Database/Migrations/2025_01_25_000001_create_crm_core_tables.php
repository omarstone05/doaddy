<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leads
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Lead Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            
            // Contact Details
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->default('Zambia');
            $table->string('website')->nullable();
            
            // Lead Details
            $table->enum('lead_source', [
                'website', 'referral', 'social_media', 'cold_call', 'event',
                'partner', 'advertisement', 'walk_in', 'whatsapp', 'other'
            ])->default('other');
            $table->string('source_details')->nullable();
            $table->enum('lead_status', [
                'new', 'contacted', 'qualified', 'unqualified', 'converted', 'lost', 'nurturing'
            ])->default('new');
            
            // Qualification
            $table->integer('lead_score')->default(0);
            $table->enum('rating', ['hot', 'warm', 'cold'])->nullable();
            $table->text('qualification_notes')->nullable();
            
            // Interest
            $table->json('interested_in')->nullable();
            $table->string('budget_range')->nullable();
            $table->string('timeline')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            
            // Assignment
            $table->uuid('assigned_to')->nullable();
            $table->timestamp('assigned_date')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->date('next_follow_up_date')->nullable();
            
            // Conversion
            $table->boolean('is_converted')->default(false);
            $table->uuid('converted_to_contact_id')->nullable();
            $table->uuid('converted_to_opportunity_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->uuid('converted_by')->nullable();
            
            // Loss Tracking
            $table->boolean('is_lost')->default(false);
            $table->string('lost_reason')->nullable();
            $table->timestamp('lost_at')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            
            // Communication Preferences
            $table->boolean('do_not_call')->default(false);
            $table->boolean('do_not_email')->default(false);
            $table->boolean('do_not_whatsapp')->default(false);
            $table->boolean('email_bounced')->default(false);
            $table->boolean('unsubscribed')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'lead_status']);
            $table->index('assigned_to');
            $table->index('email');
            $table->index('phone');
            $table->index('lead_score');
            $table->index('next_follow_up_date');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Contacts (extends customers)
        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('customer_id')->nullable(); // links to existing customers table
            
            // Contact Type
            $table->enum('contact_type', ['person', 'company'])->default('person');
            
            // Person Details
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name'); // computed or stored
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->date('date_of_birth')->nullable();
            
            // Company Details
            $table->string('company_name')->nullable();
            $table->enum('company_size', ['solo', '1-10', '11-50', '51-200', '201-500', '500+'])->nullable();
            $table->string('industry')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            
            // Contact Information
            $table->string('email_primary')->nullable();
            $table->string('email_secondary')->nullable();
            $table->string('phone_primary');
            $table->string('phone_secondary')->nullable();
            $table->string('mobile')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('fax')->nullable();
            
            // Address
            $table->text('address_line1')->nullable();
            $table->text('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Zambia');
            
            // Online Presence
            $table->string('website')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_handle')->nullable();
            
            // Relationship
            $table->enum('account_type', ['prospect', 'customer', 'partner', 'vendor', 'competitor'])->default('prospect');
            $table->enum('relationship_status', ['active', 'inactive', 'churned', 'prospect'])->default('prospect');
            $table->date('customer_since')->nullable();
            
            // Assignment & Ownership
            $table->uuid('owner_id');
            $table->json('team_ids')->nullable();
            $table->string('territory')->nullable();
            
            // Value & Segmentation
            $table->decimal('customer_lifetime_value', 15, 2)->default(0);
            $table->decimal('total_purchases', 15, 2)->default(0);
            $table->integer('total_opportunities')->default(0);
            $table->integer('won_opportunities')->default(0);
            $table->integer('lost_opportunities')->default(0);
            
            $table->enum('customer_tier', ['platinum', 'gold', 'silver', 'bronze'])->nullable();
            $table->string('segment')->nullable();
            
            // Engagement
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('last_purchase_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('engagement_score')->default(0);
            
            // Preferences
            $table->enum('preferred_contact_method', ['email', 'phone', 'whatsapp', 'sms', 'in_person'])->default('email');
            $table->string('preferred_language')->default('English');
            $table->string('best_time_to_contact')->nullable();
            
            // Consent & Privacy
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('do_not_call')->default(false);
            $table->boolean('do_not_email')->default(false);
            $table->boolean('gdpr_consent')->default(false);
            $table->timestamp('consent_date')->nullable();
            
            // Social Media
            $table->json('social_profiles')->nullable();
            
            // Parent/Child Relationships
            $table->uuid('parent_contact_id')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            
            // Metadata
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('avatar')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'is_active']);
            $table->index('customer_id');
            $table->index('owner_id');
            $table->index('email_primary');
            $table->index('phone_primary');
            $table->index('company_name');
            
            // Fulltext index only for MySQL/PostgreSQL (not SQLite)
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['full_name', 'company_name', 'email_primary']);
            }
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Opportunities
        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Opportunity Details
            $table->string('opportunity_number')->unique();
            $table->string('name');
            $table->uuid('contact_id');
            $table->uuid('account_id')->nullable();
            $table->enum('type', ['new_business', 'existing_customer', 'renewal', 'upsell'])->default('new_business');
            
            // Value
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('ZMW');
            $table->boolean('recurring_revenue')->default(false);
            $table->enum('recurring_period', ['monthly', 'quarterly', 'annual'])->nullable();
            
            // Pipeline
            $table->string('stage');
            $table->uuid('pipeline_id')->nullable();
            $table->integer('probability')->default(0);
            $table->decimal('expected_revenue', 15, 2)->nullable();
            
            // Dates
            $table->date('created_date');
            $table->date('expected_close_date');
            $table->date('actual_close_date')->nullable();
            $table->date('last_stage_change_date')->nullable();
            $table->integer('days_in_stage')->default(0);
            
            // Assignment
            $table->uuid('owner_id');
            $table->uuid('sales_team_id')->nullable();
            $table->string('territory')->nullable();
            
            // Competition
            $table->json('competitors')->nullable();
            $table->enum('our_position', ['leader', 'contender', 'follower', 'unknown'])->nullable();
            
            // Source
            $table->string('lead_source')->nullable();
            $table->uuid('campaign_id')->nullable();
            $table->uuid('referrer_contact_id')->nullable();
            
            // Status
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->string('loss_reason')->nullable();
            $table->text('loss_reason_details')->nullable();
            
            // Next Steps
            $table->string('next_step')->nullable();
            $table->date('next_step_date')->nullable();
            
            // Products
            $table->json('products_interested')->nullable();
            
            // Metrics
            $table->integer('activities_count')->default(0);
            $table->integer('emails_count')->default(0);
            $table->integer('calls_count')->default(0);
            $table->integer('meetings_count')->default(0);
            
            // Metadata
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            
            // Automation
            $table->boolean('automated_reminders_enabled')->default(true);
            $table->boolean('stale_alert_sent')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'stage']);
            $table->index('contact_id');
            $table->index('owner_id');
            $table->index('expected_close_date');
            $table->index(['is_won', 'is_lost']);
            $table->index('opportunity_number');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
                  
            $table->foreign('contact_id')
                  ->references('id')
                  ->on('crm_contacts')
                  ->onDelete('cascade');
        });

        // Opportunity Products (Line Items)
        Schema::create('crm_opportunity_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('opportunity_id');
            $table->uuid('product_id')->nullable();
            
            // Product Details
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->string('product_code')->nullable();
            
            // Quantity & Pricing
            $table->decimal('quantity', 15, 3)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percentage', 8, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(16);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('total', 15, 2);
            
            // Recurring
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_period', ['monthly', 'quarterly', 'annual'])->nullable();
            $table->integer('recurring_months')->nullable();
            $table->decimal('total_contract_value', 15, 2)->nullable();
            
            // Metadata
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('opportunity_id');
            $table->index('product_id');
            
            $table->foreign('opportunity_id')
                  ->references('id')
                  ->on('crm_opportunities')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_opportunity_products');
        Schema::dropIfExists('crm_opportunities');
        Schema::dropIfExists('crm_contacts');
        Schema::dropIfExists('crm_leads');
    }
};


