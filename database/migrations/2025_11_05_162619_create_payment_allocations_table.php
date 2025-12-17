<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
                $table->foreign('payment_id')
                    ->references('id')
                    ->on('payments')
                    ->cascadeOnDelete();
            });
        }
        
        if (Schema::hasTable('payment_allocations') && Schema::hasTable('invoices')) {
            Schema::table('payment_allocations', function (Blueprint $table) {
                $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
