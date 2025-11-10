<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('team_member_id');
            $table->uuid('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('number_of_days');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->uuid('approved_by_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('team_member_id')->references('id')->on('team_members')->onDelete('cascade');
            // Foreign keys for leave_type_id and approved_by_id will be added after those tables exist
            $table->index(['organization_id', 'team_member_id', 'status']);
            $table->index(['start_date', 'end_date']);
            });
            
            // Add foreign keys after referenced tables exist
            if (Schema::hasTable('leave_types')) {
                Schema::table('leave_requests', function (Blueprint $table) {
                    $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                    if (empty($foreignKeys)) {
                        $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
                    }
                });
            }
            
            if (Schema::hasTable('users')) {
                Schema::table('leave_requests', function (Blueprint $table) {
                    $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'approved_by_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                    if (empty($foreignKeys)) {
                        $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
