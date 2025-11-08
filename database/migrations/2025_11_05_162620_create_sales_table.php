<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('sale_number')->unique();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'mobile_money', 'card', 'credit']);
            $table->string('payment_reference')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->uuid('money_account_id');
            $table->uuid('department_id')->nullable();
            $table->uuid('cashier_id');
            $table->uuid('register_session_id')->nullable();
            $table->enum('status', ['completed', 'voided', 'returned'])->default('completed');
            $table->date('sale_date');
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('money_account_id')->references('id')->on('money_accounts');
            $table->foreign('cashier_id')->references('id')->on('team_members');
            $table->foreign('register_session_id')->references('id')->on('register_sessions')->onDelete('set null');
            $table->index(['organization_id', 'sale_date']);
            $table->index('cashier_id');
            $table->index('customer_id');
            $table->index('register_session_id');
            $table->index('sale_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
