<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id');
                $table->uuid('customer_id');
                $table->string('payment_number')->unique();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('ZMW');
                $table->date('payment_date');
                $table->enum('payment_method', ['cash', 'mobile_money', 'card', 'bank_transfer', 'cheque', 'other']);
                $table->string('payment_reference')->nullable();
                $table->uuid('money_account_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
                $table->foreign('money_account_id')->references('id')->on('money_accounts')->onDelete('set null');
                $table->index(['organization_id', 'payment_date']);
                $table->index('payment_number');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
