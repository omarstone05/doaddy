<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_returns')) {
            Schema::create('sale_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('sale_id');
            $table->string('return_number')->unique();
            $table->decimal('return_amount', 12, 2);
            $table->text('return_reason')->nullable();
            $table->enum('refund_method', ['cash', 'mobile_money', 'card', 'credit_note']);
            $table->string('refund_reference')->nullable();
            $table->uuid('processed_by_id');
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->date('return_date');
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            // Foreign key for processed_by_id will be added after team_members table exists
            $table->index(['organization_id', 'return_date']);
            $table->index('sale_id');
            });
            
            // Add foreign key after team_members table exists
            if (Schema::hasTable('team_members')) {
                Schema::table('sale_returns', function (Blueprint $table) {
                    $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_returns' AND COLUMN_NAME = 'processed_by_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                    if (empty($foreignKeys)) {
                        $table->foreign('processed_by_id')->references('id')->on('team_members')->onDelete('cascade');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
