<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->foreignUuid('account_id')->constrained('accounts')->onDelete('cascade');
            $table->date('balance_date');
            $table->decimal('debit_balance', 15, 2)->default(0);
            $table->decimal('credit_balance', 15, 2)->default(0);
            $table->decimal('net_balance', 15, 2)->default(0); // Calculated based on normal balance
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            
            $table->unique(['account_id', 'balance_date']);
            $table->index(['organization_id', 'balance_date']);
            $table->index(['account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};

