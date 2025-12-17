<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Activities
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Activity Type
            $table->enum('activity_type', [
                'call', 'meeting', 'email', 'whatsapp', 'sms', 'task', 'note', 'linkedin', 'other'
            ]);
            $table->enum('direction', ['inbound', 'outbound'])->nullable();
            
            // Related To (polymorphic)
            $table->string('related_to_type'); // lead, contact, opportunity, quote
            $table->uuid('related_to_id');
            
            // Activity Details
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            
            // Dates & Times
            $table->date('activity_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->boolean('all_day')->default(false);
            
            // Status
            $table->enum('status', ['planned', 'completed', 'cancelled', 'no_show'])->default('planned');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            
            // Assignment
            $table->uuid('owner_id');
            $table->json('participants')->nullable();
            
            // Outcome
            $table->string('outcome')->nullable();
            $table->text('outcome_notes')->nullable();
            
            // Next Action
            $table->enum('next_activity_type', [
                'call', 'meeting', 'email', 'whatsapp', 'sms', 'task', 'note', 'other'
            ])->nullable();
            $table->date('next_activity_date')->nullable();
            $table->text('next_activity_notes')->nullable();
            
            // Communication Details
            $table->string('from_address')->nullable();
            $table->json('to_addresses')->nullable();
            $table->json('cc_addresses')->nullable();
            $table->string('message_id')->nullable();
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->json('attachments')->nullable();
            
            // Automation
            $table->boolean('is_automated')->default(false);
            $table->uuid('workflow_id')->nullable();
            
            // Reminder
            $table->boolean('reminder_enabled')->default(false);
            $table->timestamp('reminder_datetime')->nullable();
            $table->boolean('reminder_sent')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'activity_type']);
            $table->index(['related_to_type', 'related_to_id']);
            $table->index(['owner_id', 'activity_date']);
            $table->index('status');
            $table->index('is_completed');
            $table->index('reminder_datetime');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Tasks
        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Task Details
            $table->string('subject');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Related To (polymorphic)
            $table->string('related_to_type')->nullable();
            $table->uuid('related_to_id')->nullable();
            
            // Assignment
            $table->uuid('assigned_to');
            $table->uuid('created_by');
            
            // Dates
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Status
            $table->enum('status', ['not_started', 'in_progress', 'waiting', 'completed', 'cancelled'])->default('not_started');
            $table->boolean('is_completed')->default(false);
            
            // Reminder
            $table->boolean('reminder_enabled')->default(false);
            $table->timestamp('reminder_datetime')->nullable();
            $table->boolean('reminder_sent')->default(false);
            
            // Recurrence
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_pattern')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index(['assigned_to', 'due_date']);
            $table->index(['related_to_type', 'related_to_id']);
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Emails
        Schema::create('crm_emails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Email Details
            $table->enum('email_type', ['sent', 'received', 'draft'])->default('sent');
            $table->string('from_address');
            $table->json('to_addresses');
            $table->json('cc_addresses')->nullable();
            $table->json('bcc_addresses')->nullable();
            $table->string('subject');
            $table->text('body_html')->nullable();
            $table->text('body_text')->nullable();
            
            // Related To (polymorphic)
            $table->string('related_to_type')->nullable();
            $table->uuid('related_to_id')->nullable();
            
            // Status
            $table->boolean('is_read')->default(false);
            $table->boolean('is_replied')->default(false);
            $table->boolean('bounced')->default(false);
            $table->integer('opened_count')->default(0);
            $table->timestamp('last_opened_at')->nullable();
            $table->integer('clicked_count')->default(0);
            
            // Threading
            $table->string('thread_id')->nullable();
            $table->string('in_reply_to')->nullable();
            $table->string('message_id')->unique();
            
            // Attachments
            $table->boolean('has_attachments')->default(false);
            $table->json('attachments')->nullable();
            
            // Template
            $table->uuid('template_id')->nullable();
            
            // Sender
            $table->uuid('sent_by')->nullable();
            
            // Dates
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'email_type']);
            $table->index(['related_to_type', 'related_to_id']);
            $table->index('message_id');
            $table->index('thread_id');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // WhatsApp Messages (Zambian focus!)
        Schema::create('crm_whatsapp_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Message Details
            $table->enum('message_type', ['sent', 'received', 'template'])->default('sent');
            $table->string('from_number');
            $table->string('to_number');
            $table->text('message_body');
            $table->enum('message_status', [
                'queued', 'sent', 'delivered', 'read', 'failed'
            ])->default('queued');
            
            // Related To (polymorphic)
            $table->string('related_to_type')->nullable();
            $table->uuid('related_to_id')->nullable();
            
            // Media
            $table->boolean('has_media')->default(false);
            $table->string('media_type')->nullable();
            $table->string('media_url')->nullable();
            
            // Template
            $table->uuid('template_id')->nullable();
            $table->json('template_variables')->nullable();
            
            // Status Updates
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Sender
            $table->uuid('sent_by')->nullable();
            
            // Automation
            $table->boolean('is_automated')->default(false);
            $table->uuid('campaign_id')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'message_type']);
            $table->index(['related_to_type', 'related_to_id']);
            $table->index('from_number');
            $table->index('to_number');
            $table->index('message_status');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // SMS Messages
        Schema::create('crm_sms_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Message Details
            $table->enum('message_type', ['sent', 'received'])->default('sent');
            $table->string('from_number');
            $table->string('to_number');
            $table->text('message_body');
            $table->enum('message_status', [
                'queued', 'sent', 'delivered', 'failed'
            ])->default('queued');
            
            // Related To (polymorphic)
            $table->string('related_to_type')->nullable();
            $table->uuid('related_to_id')->nullable();
            
            // Status Updates
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Sender
            $table->uuid('sent_by')->nullable();
            
            // Provider
            $table->string('provider')->nullable(); // Africa's Talking, etc.
            $table->string('provider_message_id')->nullable();
            
            // Automation
            $table->boolean('is_automated')->default(false);
            $table->uuid('campaign_id')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'message_type']);
            $table->index(['related_to_type', 'related_to_id']);
            $table->index('message_status');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_sms_messages');
        Schema::dropIfExists('crm_whatsapp_messages');
        Schema::dropIfExists('crm_emails');
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('crm_activities');
    }
};
