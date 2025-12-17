<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Security events table for audit logging of authentication and security-related events.
     * This is a pre-requisite for SSO migration and general security best practices.
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable(); // Nullable for failed login attempts
            
            // Event classification
            $table->string('event_type', 50); // login_success, login_failure, logout, password_change, mfa_enable, etc.
            $table->enum('event_status', ['success', 'failure', 'warning']);
            $table->string('event_source', 50)->default('web'); // web, api, mobile, sso
            
            // Request context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 100)->nullable();
            
            // Additional context
            $table->string('email', 255)->nullable(); // For failed attempts where user_id is null
            $table->string('organization_id', 36)->nullable();
            
            // Geo-location (optional, populated by IP lookup service)
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('region', 100)->nullable();
            
            // Details
            $table->json('metadata')->nullable(); // Additional event-specific data
            $table->text('failure_reason')->nullable(); // For failed events
            
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index('user_id');
            $table->index('event_type');
            $table->index('event_status');
            $table->index('ip_address');
            $table->index('created_at');
            $table->index(['user_id', 'event_type']);
            $table->index(['ip_address', 'event_type', 'created_at']); // For rate limiting
            
            // Foreign key (soft - user may be deleted but we keep audit trail)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};

