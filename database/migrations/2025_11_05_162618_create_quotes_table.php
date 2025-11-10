<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('customer_id');
            $table->string('quote_number')->unique();
            $table->date('quote_date');
            $table->date('expiry_date')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->index(['organization_id', 'quote_date']);
            $table->index('quote_number');
        });
        
        // Add foreign key constraint to invoices table if it exists
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'quote_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            });
        }
        
        // Add foreign key constraint to quote_items table if it exists
        if (Schema::hasTable('quote_items') && Schema::hasColumn('quote_items', 'quote_id')) {
            Schema::table('quote_items', function (Blueprint $table) {
                $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
