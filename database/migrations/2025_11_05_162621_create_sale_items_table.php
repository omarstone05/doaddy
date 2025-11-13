<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id');
            $table->uuid('goods_service_id');
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            // Foreign key for goods_service_id will be added after goods_and_services table exists
            $table->index('sale_id');
            $table->index('goods_service_id');
            });
        }

        if (Schema::hasTable('sale_items') && Schema::hasTable('goods_and_services')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->foreign('goods_service_id')
                    ->references('id')
                    ->on('goods_and_services');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
