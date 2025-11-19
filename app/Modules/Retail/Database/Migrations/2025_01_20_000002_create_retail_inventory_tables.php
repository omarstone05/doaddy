<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product Variants
        Schema::create('retail_product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('organization_id');
            
            // Variant Details
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->string('option1_name')->nullable();
            $table->string('option1_value')->nullable();
            $table->string('option2_name')->nullable();
            $table->string('option2_value')->nullable();
            $table->string('option3_name')->nullable();
            $table->string('option3_value')->nullable();
            
            // Inventory
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('minimum_stock', 15, 3)->default(0);
            
            // Pricing
            $table->decimal('cost_price', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->decimal('price_difference', 15, 2)->default(0);
            
            // Physical
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('image')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'is_active']);
            
            $table->foreign('product_id')
                  ->references('id')
                  ->on('retail_products')
                  ->onDelete('cascade');
        });

        // Product Assemblies (Bill of Materials)
        Schema::create('retail_product_assemblies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('assembled_product_id');
            
            // Assembly Details
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity_produced', 15, 3)->default(1);
            $table->integer('assembly_time')->nullable(); // minutes
            $table->decimal('labor_cost', 15, 2)->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'assembled_product_id']);
            
            $table->foreign('assembled_product_id')
                  ->references('id')
                  ->on('retail_products')
                  ->onDelete('cascade');
        });

        // Assembly Components
        Schema::create('retail_assembly_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assembly_id');
            $table->uuid('component_product_id');
            
            // Quantities
            $table->decimal('quantity_needed', 15, 3);
            $table->string('unit_of_measure');
            
            // Metadata
            $table->string('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('assembly_id');
            $table->index('component_product_id');
            
            $table->foreign('assembly_id')
                  ->references('id')
                  ->on('retail_product_assemblies')
                  ->onDelete('cascade');
                  
            $table->foreign('component_product_id')
                  ->references('id')
                  ->on('retail_products')
                  ->onDelete('cascade');
        });

        // Stock Movements
        Schema::create('retail_stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('location_id')->nullable();
            
            // Product
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            
            // Movement
            $table->enum('movement_type', [
                'purchase', 'sale', 'adjustment', 'assembly', 
                'transfer', 'return', 'wastage'
            ]);
            $table->decimal('quantity', 15, 3);
            $table->decimal('previous_stock', 15, 3);
            $table->decimal('new_stock', 15, 3);
            
            // Reference
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            
            // Details
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            
            // User
            $table->uuid('created_by');
            
            $table->timestamp('created_at');
            
            // Indexes
            $table->index(['product_id', 'created_at']);
            $table->index(['organization_id', 'movement_type']);
            $table->index('location_id');
            
            $table->foreign('product_id')
                  ->references('id')
                  ->on('retail_products')
                  ->onDelete('cascade');
        });

        // Suppliers
        Schema::create('retail_suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Identity
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('supplier_code')->nullable()->unique();
            
            // Contact
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Zambia');
            
            // Financial
            $table->string('payment_terms')->nullable();
            $table->string('tax_number')->nullable();
            $table->json('bank_details')->nullable();
            
            // Performance
            $table->decimal('total_purchased', 15, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('average_delivery_days')->nullable();
            $table->integer('rating')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'is_active']);
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Purchase Orders
        Schema::create('retail_purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('location_id')->nullable();
            
            // Order Details
            $table->string('po_number')->unique();
            $table->uuid('supplier_id');
            $table->enum('status', [
                'draft', 'sent', 'confirmed', 'partial', 'received', 'cancelled'
            ])->default('draft');
            
            // Dates
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('received_date')->nullable();
            
            // Financial
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('ZMW');
            
            // Payment
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->string('payment_terms')->nullable();
            
            // Created By
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->uuid('received_by')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index('supplier_id');
            $table->index('po_number');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
                  
            $table->foreign('supplier_id')
                  ->references('id')
                  ->on('retail_suppliers')
                  ->onDelete('restrict');
        });

        // Purchase Order Items
        Schema::create('retail_purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id');
            
            // Product
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->string('description');
            
            // Quantities
            $table->decimal('quantity_ordered', 15, 3);
            $table->decimal('quantity_received', 15, 3)->default(0);
            $table->decimal('quantity_pending', 15, 3);
            
            // Pricing
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            
            // Status
            $table->enum('status', ['pending', 'partial', 'received'])->default('pending');
            
            // Metadata
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('purchase_order_id');
            $table->index('product_id');
            
            $table->foreign('purchase_order_id')
                  ->references('id')
                  ->on('retail_purchase_orders')
                  ->onDelete('cascade');
                  
            $table->foreign('product_id')
                  ->references('id')
                  ->on('retail_products')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_purchase_order_items');
        Schema::dropIfExists('retail_purchase_orders');
        Schema::dropIfExists('retail_suppliers');
        Schema::dropIfExists('retail_stock_movements');
        Schema::dropIfExists('retail_assembly_components');
        Schema::dropIfExists('retail_product_assemblies');
        Schema::dropIfExists('retail_product_variants');
    }
};

