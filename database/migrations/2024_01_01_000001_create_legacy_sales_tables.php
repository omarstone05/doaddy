<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the legacy sales and sale_items tables.
 * 
 * These tables existed in production before the Retail module was added.
 * The Retail module created retail_sales and retail_sale_items tables,
 * but production data remained in the original sales/sale_items tables.
 * 
 * This migration ensures local/test environments have the same structure
 * as production for proper testing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Create sales table if it doesn't exist
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id');
                $table->uuid('location_id')->nullable();
                
                // Sale Details
                $table->string('sale_number')->unique();
                $table->string('transaction_type')->default('sale'); // sale, return, exchange
                $table->string('status')->default('completed'); // completed, pending, cancelled, refunded, voided, returned
                
                // Date & Time
                $table->date('sale_date');
                $table->time('sale_time')->nullable();
                
                // Customer
                $table->uuid('customer_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                
                // Financial
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2);
                $table->decimal('amount_paid', 15, 2)->default(0);
                $table->decimal('change_given', 15, 2)->default(0);
                $table->string('currency', 3)->default('ZMW');
                
                // Payment
                $table->string('payment_method')->default('cash'); // cash, mobile_money, card, credit, split
                $table->string('payment_reference')->nullable();
                $table->string('mobile_money_provider')->nullable();
                $table->string('mobile_money_number')->nullable();
                $table->string('card_last_four')->nullable();
                
                // Legacy fields (production compatibility)
                $table->uuid('money_account_id')->nullable();
                $table->uuid('department_id')->nullable();
                $table->uuid('register_session_id')->nullable();
                
                // Staff
                $table->uuid('cashier_id')->nullable();
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
                $table->index('customer_id');
                $table->index('cashier_id');
                $table->index('sale_number');
                $table->index('shift_id');
            });
        }

        // Create sale_items table if it doesn't exist
        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('sale_id');
                
                // Product references
                $table->uuid('product_id')->nullable();
                $table->uuid('variant_id')->nullable();
                $table->uuid('goods_service_id')->nullable(); // Legacy field
                $table->string('product_name');
                $table->string('sku')->nullable();
                $table->string('barcode')->nullable();
                
                // Quantities
                $table->decimal('quantity', 15, 3);
                $table->string('unit_of_measure')->nullable();
                
                // Pricing
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('discount_per_item', 15, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                
                // Totals
                $table->decimal('total', 12, 2)->default(0); // Legacy field
                $table->decimal('line_total', 15, 2)->default(0);
                $table->decimal('line_cost', 15, 2)->default(0);
                $table->decimal('line_profit', 15, 2)->default(0);
                
                // Legacy fields
                $table->decimal('cost_price', 10, 2)->nullable();
                $table->integer('display_order')->default(0);
                
                // Metadata
                $table->text('notes')->nullable();
                
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index('sale_id');
                $table->index('product_id');
                $table->index('goods_service_id');

                // Foreign key
                $table->foreign('sale_id')
                    ->references('id')
                    ->on('sales')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
