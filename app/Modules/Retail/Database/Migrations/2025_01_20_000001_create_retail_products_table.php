<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('location_id')->nullable();
            
            // Identity
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('brand')->nullable();
            
            // Type
            $table->enum('product_type', ['simple', 'variant', 'assembled', 'service'])->default('simple');
            $table->boolean('is_for_sale')->default(true);
            $table->boolean('is_raw_material')->default(false);
            
            // Inventory
            $table->boolean('track_stock')->default(true);
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('minimum_stock', 15, 3)->default(0);
            $table->decimal('maximum_stock', 15, 3)->nullable();
            $table->decimal('reorder_point', 15, 3)->nullable();
            $table->decimal('reorder_quantity', 15, 3)->nullable();
            
            // Pricing
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('compare_at_price', 15, 2)->nullable();
            $table->decimal('profit_margin', 8, 2)->default(0);
            $table->decimal('markup_percentage', 8, 2)->default(0);
            
            // Tax
            $table->decimal('tax_rate', 5, 2)->default(16.00);
            $table->boolean('is_taxable')->default(true);
            
            // Physical
            $table->string('unit_of_measure')->default('piece');
            $table->decimal('weight', 10, 3)->nullable();
            $table->json('dimensions')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->boolean('allows_backorder')->default(false);
            
            // Tracking
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->uuid('supplier_id')->nullable();
            
            // Images
            $table->json('images')->nullable();
            $table->string('thumbnail')->nullable();
            
            // Metadata
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index(['is_for_sale', 'status']);
            $table->index('category_id');
            $table->index('current_stock');
            
            // Foreign keys
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_products');
    }
};

