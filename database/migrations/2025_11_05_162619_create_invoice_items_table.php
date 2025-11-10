<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        
        // Add foreign key for goods_service_id if table exists and constraint doesn't exist
        if (Schema::hasTable('goods_and_services') && Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('invoice_items', 'goods_service_id')) {
                    $table->uuid('goods_service_id')->nullable()->after('invoice_id');
                }
                // Check if foreign key doesn't exist before adding
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoice_items' AND COLUMN_NAME = 'goods_service_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                if (empty($foreignKeys)) {
                    $table->foreign('goods_service_id')->references('id')->on('goods_and_services')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
