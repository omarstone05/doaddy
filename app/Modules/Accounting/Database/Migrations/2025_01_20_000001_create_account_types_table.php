<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name'); // Assets, Liabilities, Equity, Revenue, Expenses
            $table->string('code', 10)->unique(); // ASSET, LIAB, EQUITY, REV, EXP
            $table->enum('category', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']); // Which side increases the account
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};

