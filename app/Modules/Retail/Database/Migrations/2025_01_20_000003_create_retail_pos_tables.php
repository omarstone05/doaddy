<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Locations (Multi-Store) - Create first as it's referenced
        Schema::create('retail_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Location Details
            $table->string('name');
            $table->string('code')->nullable();
            $table->enum('type', ['store', 'warehouse', 'online'])->default('store');
            
            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            
            // Metadata
            $table->json('settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'is_active']);
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Categories
        Schema::create('retail_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            $table->string('name');
            $table->uuid('parent_id')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'parent_id']);
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Customers
        Schema::create('retail_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            
            // Identity
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            
            // Loyalty
            $table->integer('loyalty_points')->default(0);
            $table->decimal('total_purchases', 15, 2)->default(0);
            $table->decimal('lifetime_value', 15, 2)->default(0);
            $table->date('last_purchase_date')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'is_active']);
            $table->index('phone');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Shifts / Cash Drawer Sessions
        Schema::create('retail_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('location_id')->nullable();
            
            // Shift Details
            $table->string('shift_number')->unique();
            $table->uuid('cashier_id');
            $table->enum('status', ['open', 'closed'])->default('open');
            
            // Cash Float
            $table->decimal('opening_cash', 15, 2);
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable();
            
            // Date & Time
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            
            // Totals (calculated from sales)
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_cash_sales', 15, 2)->default(0);
            $table->decimal('total_mobile_money_sales', 15, 2)->default(0);
            $table->decimal('total_card_sales', 15, 2)->default(0);
            $table->decimal('total_refunds', 15, 2)->default(0);
            
            // Counts
            $table->integer('number_of_sales')->default(0);
            $table->integer('number_of_refunds')->default(0);
            
            // Metadata
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index(['cashier_id', 'opened_at']);
            $table->index('shift_number');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Sales / POS Transactions
        Schema::create('retail_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('location_id')->nullable();
            
            // Sale Details
            $table->string('sale_number')->unique();
            $table->enum('transaction_type', ['sale', 'return', 'exchange'])->default('sale');
            $table->enum('status', ['completed', 'pending', 'cancelled', 'refunded'])->default('completed');
            
            // Date & Time
            $table->date('sale_date');
            $table->time('sale_time');
            
            // Customer
            $table->uuid('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            
            // Financial
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('change_given', 15, 2)->default(0);
            $table->string('currency', 3)->default('ZMW');
            
            // Payment
            $table->enum('payment_method', [
                'cash', 'mobile_money', 'card', 'credit', 'split'
            ])->default('cash');
            $table->string('mobile_money_provider')->nullable();
            $table->string('mobile_money_number')->nullable();
            $table->string('card_last_four')->nullable();
            
            // Staff
            $table->uuid('cashier_id');
            $table->uuid('shift_id')->nullable();
            
            // Profit
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->decimal('profit_margin', 8, 2)->default(0);
            
            // Metadata
            $table->text('notes')->nullable();
            $table->boolean('receipt_printed')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['organization_id', 'sale_date']);
            $table->index(['sale_number']);
            $table->index(['cashier_id', 'sale_date']);
            $table->index(['customer_id']);
            $table->index(['shift_id']);
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Sale Items
        Schema::create('retail_sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id');
            
            // Product
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->string('product_name');
            $table->string('sku');
            
            // Quantities
            $table->decimal('quantity', 15, 3);
            $table->string('unit_of_measure');
            
            // Pricing
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_per_item', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('line_total', 15, 2);
            
            // Profit
            $table->decimal('line_cost', 15, 2);
            $table->decimal('line_profit', 15, 2);
            
            // Metadata
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('sale_id');
            $table->index('product_id');
            
            $table->foreign('sale_id')
                  ->references('id')
                  ->on('retail_sales')
                  ->onDelete('cascade');
        });

        // Cash Movements
        Schema::create('retail_cash_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shift_id');
            $table->uuid('organization_id');
            
            // Movement
            $table->enum('movement_type', [
                'cash_in', 'cash_out', 'sale', 'refund', 'payout'
            ]);
            $table->decimal('amount', 15, 2);
            $table->string('reason')->nullable();
            
            // Reference
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            
            // User
            $table->uuid('created_by');
            
            // Metadata
            $table->text('notes')->nullable();
            
            $table->timestamp('created_at');
            
            // Indexes
            $table->index(['shift_id', 'movement_type']);
            $table->index('created_at');
            
            $table->foreign('shift_id')
                  ->references('id')
                  ->on('retail_shifts')
                  ->onDelete('cascade');
        });

        // Stock Adjustments
        Schema::create('retail_stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('location_id')->nullable();
            
            // Adjustment Details
            $table->string('adjustment_number')->unique();
            $table->enum('adjustment_type', [
                'count', 'damage', 'loss', 'found', 'correction'
            ]);
            $table->enum('status', ['draft', 'completed'])->default('draft');
            
            // Date
            $table->date('adjustment_date');
            
            // User
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            
            // Metadata
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index('adjustment_number');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Stock Adjustment Items
        Schema::create('retail_stock_adjustment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('adjustment_id');
            
            // Product
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            
            // Quantities
            $table->decimal('expected_quantity', 15, 3)->nullable();
            $table->decimal('actual_quantity', 15, 3);
            $table->decimal('difference', 15, 3);
            
            // Value
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_value', 15, 2);
            
            $table->timestamps();
            
            // Indexes
            $table->index('adjustment_id');
            $table->index('product_id');
            
            $table->foreign('adjustment_id')
                  ->references('id')
                  ->on('retail_stock_adjustments')
                  ->onDelete('cascade');
                  
            $table->foreign('product_id')
                  ->references('id')
                  ->on('retail_products')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_stock_adjustment_items');
        Schema::dropIfExists('retail_stock_adjustments');
        Schema::dropIfExists('retail_cash_movements');
        Schema::dropIfExists('retail_sale_items');
        Schema::dropIfExists('retail_sales');
        Schema::dropIfExists('retail_shifts');
        Schema::dropIfExists('retail_customers');
        Schema::dropIfExists('retail_categories');
        Schema::dropIfExists('retail_locations');
    }
};

