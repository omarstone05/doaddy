<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('register_sessions')) {
            Schema::create('register_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('session_number')->unique();
            $table->uuid('money_account_id');
            $table->uuid('department_id')->nullable();
            $table->uuid('opened_by_id');
            $table->timestamp('opening_date');
            $table->decimal('opening_float', 12, 2);
            $table->uuid('closed_by_id')->nullable();
            $table->timestamp('closing_date')->nullable();
            $table->decimal('closing_count', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('cash_sales', 12, 2)->default(0);
            $table->decimal('mobile_money_sales', 12, 2)->default(0);
            $table->decimal('card_sales', 12, 2)->default(0);
            $table->decimal('credit_sales', 12, 2)->default(0);
            $table->decimal('cash_paid_out', 12, 2)->default(0);
            $table->decimal('cash_received', 12, 2)->default(0);
            $table->enum('status', ['open', 'closed', 'reconciled'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('money_account_id')->references('id')->on('money_accounts');
            // Foreign keys for opened_by_id and closed_by_id will be added after team_members table exists
            $table->index(['organization_id', 'opening_date']);
            $table->index('money_account_id');
            $table->index('status');
            });
            
            // Add foreign keys after team_members table exists
            if (Schema::hasTable('team_members')) {
                Schema::table('register_sessions', function (Blueprint $table) {
                    $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'register_sessions' AND COLUMN_NAME = 'opened_by_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                    if (empty($foreignKeys)) {
                        $table->foreign('opened_by_id')->references('id')->on('team_members');
                    }
                    $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'register_sessions' AND COLUMN_NAME = 'closed_by_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                    if (empty($foreignKeys)) {
                        $table->foreign('closed_by_id')->references('id')->on('team_members')->onDelete('set null');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('register_sessions');
    }
};
