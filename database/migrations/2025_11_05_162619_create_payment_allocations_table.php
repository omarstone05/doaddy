<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_allocations')) {
            Schema::create('payment_allocations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('payment_id');
                $table->uuid('invoice_id');
                $table->decimal('amount', 12, 2);
                $table->timestamps();
                
                // Foreign keys will be added after payments table exists
                $table->index(['payment_id', 'invoice_id']);
            });
        }
        
        // Add foreign keys if tables exist
        if (Schema::hasTable('payment_allocations') && Schema::hasTable('payments')) {
            Schema::table('payment_allocations', function (Blueprint $table) {
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_allocations' AND COLUMN_NAME = 'payment_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                if (empty($foreignKeys)) {
                    $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
                }
            });
        }
        
        if (Schema::hasTable('payment_allocations') && Schema::hasTable('invoices')) {
            Schema::table('payment_allocations', function (Blueprint $table) {
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_allocations' AND COLUMN_NAME = 'invoice_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                if (empty($foreignKeys)) {
                    $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
