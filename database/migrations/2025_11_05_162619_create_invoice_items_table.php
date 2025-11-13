<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('invoice_id');
                $table->uuid('goods_service_id')->nullable();
                $table->string('description');
                $table->decimal('quantity', 10, 2);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('total', 12, 2);
                $table->integer('display_order')->default(0);
                $table->timestamps();
                
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
                // Foreign key for goods_service_id will be added after goods_and_services table exists
                $table->index('invoice_id');
            });
        }
        
        if (Schema::hasTable('goods_and_services') && Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->foreign('goods_service_id')
                    ->references('id')
                    ->on('goods_and_services')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
