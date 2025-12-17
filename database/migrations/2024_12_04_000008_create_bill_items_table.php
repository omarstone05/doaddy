<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bill_items')) {
            // Table exists, just ensure product_id foreign key is correct
            if (Schema::hasColumn('bill_items', 'product_id')) {
                // Check if foreign key exists and drop it if it references wrong table
                try {
                    Schema::table('bill_items', function (Blueprint $table) {
                        $table->dropForeign(['product_id']);
                    });
                } catch (\Exception $e) {
                    // Foreign key might not exist or already dropped
                }
            }
            return;
        }

        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('order')->default(0);
            
            // Item Details
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit')->default('unit');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            
            // Accounting
            $table->string('account_code')->nullable();
            $table->string('cost_center')->nullable();
            
            $table->timestamps();

            $table->index(['bill_id', 'order']);
        });
        
        // Add foreign key separately to avoid issues if products table doesn't exist
        // The foreign key will be added by the fix migration if needed
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
