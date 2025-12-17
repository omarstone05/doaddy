<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('print_material_id')->nullable()->constrained('print_materials')->cascadeOnDelete();
            $table->string('rule_name');
            $table->enum('markup_type', ['percentage', 'fixed_amount', 'fixed_price']);
            $table->decimal('markup_value', 15, 2);
            $table->decimal('min_area', 10, 2)->nullable(); // minimum sqm for this rule
            $table->decimal('max_area', 10, 2)->nullable(); // maximum sqm for this rule
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0); // higher priority rules apply first
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('organization_id');
            $table->index('print_material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};

