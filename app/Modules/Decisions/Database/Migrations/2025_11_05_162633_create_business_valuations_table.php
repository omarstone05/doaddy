<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_valuations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->date('valuation_date');
            $table->decimal('valuation_amount', 15, 2);
            $table->string('currency', 3)->default('ZMW');
            $table->enum('valuation_method', ['revenue_multiple', 'ebitda_multiple', 'asset_based', 'discounted_cash_flow', 'market_comparable', 'other'])->default('revenue_multiple');
            $table->text('method_details')->nullable();
            $table->text('assumptions')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('valued_by_id')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('valued_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['organization_id', 'valuation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_valuations');
    }
};
