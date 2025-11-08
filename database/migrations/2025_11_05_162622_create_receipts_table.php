<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('payment_id');
            $table->string('receipt_number')->unique();
            $table->date('receipt_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->index(['organization_id', 'receipt_date']);
            $table->index('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
