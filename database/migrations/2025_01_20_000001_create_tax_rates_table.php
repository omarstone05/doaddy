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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name'); // e.g., "VAT", "Sales Tax", "GST"
            $table->string('code')->nullable(); // e.g., "VAT", "ST", "GST"
            $table->decimal('rate', 5, 2); // e.g., 16.00 for 16%
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('tax_type')->default('vat'); // vat, sales_tax, gst, custom
            $table->json('metadata')->nullable(); // For additional country-specific data
            $table->timestamps();

            $table->index('organization_id');
            $table->index(['organization_id', 'is_active']);
            $table->index(['organization_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};


