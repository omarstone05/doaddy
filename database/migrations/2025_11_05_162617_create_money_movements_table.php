<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('money_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->enum('flow_type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('ZMW');
            $table->date('transaction_date');
            $table->uuid('from_account_id')->nullable();
            $table->uuid('to_account_id')->nullable();
            $table->string('description');
            $table->string('category')->nullable();
            $table->uuid('related_type')->nullable(); // Sale, Invoice, Payment, etc.
            $table->uuid('related_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('from_account_id')->references('id')->on('money_accounts');
            $table->foreign('to_account_id')->references('id')->on('money_accounts');
            $table->index(['organization_id', 'transaction_date']);
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('money_movements');
    }
};
