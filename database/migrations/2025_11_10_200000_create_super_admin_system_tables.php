<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin roles table
        if (!Schema::hasTable('admin_roles')) {
            Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('permissions');
            $table->timestamps();
            });
        }

        // Assign admin roles to users
        if (!Schema::hasTable('admin_role_user')) {
            Schema::create('admin_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_role_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            });
        }

        // Support tickets
        if (!Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('category', ['technical', 'billing', 'feature_request', 'bug', 'other'])->default('other');
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'priority']);
            $table->index(['organization_id', 'status']);
            $table->index('ticket_number');
            });
        }

        // Support ticket messages
        if (!Schema::hasTable('support_ticket_messages')) {
            Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_internal_note')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();
            });
        }

        // Email templates
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject');
            $table->text('body');
            $table->json('variables')->nullable();
            $table->string('category')->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            });
        }

        // Email queue/log
        if (!Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('to');
            $table->string('cc')->nullable();
            $table->string('bcc')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->string('template_slug')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('organization_id');
            });
        }

        // Activity logs
        if (!Schema::hasTable('admin_activity_logs')) {
            Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['admin_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            });
        }

        // System health metrics
        if (!Schema::hasTable('system_metrics')) {
            Schema::create('system_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('active_organizations')->default(0);
            $table->integer('active_users')->default(0);
            $table->integer('new_organizations')->default(0);
            $table->integer('new_users')->default(0);
            $table->integer('support_tickets_opened')->default(0);
            $table->integer('support_tickets_resolved')->default(0);
            $table->integer('emails_sent')->default(0);
            $table->integer('api_requests')->default(0);
            $table->decimal('avg_response_time', 8, 2)->default(0);
            $table->integer('errors_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique('date');
            });
        }

        // Add admin columns to organizations
        if (!Schema::hasColumn('organizations', 'status')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->enum('status', ['active', 'suspended', 'trial', 'cancelled'])->default('trial')->after('slug');
                $table->timestamp('trial_ends_at')->nullable()->after('status');
                $table->timestamp('suspended_at')->nullable()->after('trial_ends_at');
                $table->text('suspension_reason')->nullable()->after('suspended_at');
                $table->string('billing_plan')->nullable()->after('suspension_reason');
                $table->decimal('mrr', 10, 2)->default(0)->after('billing_plan');
            });
        }

        // Add admin notes to users
        if (!Schema::hasColumn('users', 'admin_notes')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('admin_notes')->nullable()->after('remember_token');
                $table->timestamp('last_active_at')->nullable()->after('admin_notes');
            });
        }

        // Update platform_settings table if needed
        if (Schema::hasTable('platform_settings')) {
            if (!Schema::hasColumn('platform_settings', 'group')) {
                Schema::table('platform_settings', function (Blueprint $table) {
                    $table->string('group')->default('general')->after('type');
                });
            }
            if (!Schema::hasColumn('platform_settings', 'label')) {
                Schema::table('platform_settings', function (Blueprint $table) {
                    $table->string('label')->nullable()->after('group');
                });
            }
            if (!Schema::hasColumn('platform_settings', 'is_public')) {
                Schema::table('platform_settings', function (Blueprint $table) {
                    $table->boolean('is_public')->default(false)->after('description');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admin_notes')) {
                $table->dropColumn(['admin_notes', 'last_active_at']);
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'status')) {
                $table->dropColumn(['status', 'trial_ends_at', 'suspended_at', 'suspension_reason', 'billing_plan', 'mrr']);
            }
        });

        Schema::dropIfExists('system_metrics');
        Schema::dropIfExists('admin_activity_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('admin_role_user');
        Schema::dropIfExists('admin_roles');
    }
};

