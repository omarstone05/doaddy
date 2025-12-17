<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_ink_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_material_id')->constrained('print_materials')->cascadeOnDelete();
            $table->foreignId('ink_configuration_id')->constrained('ink_configurations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['print_material_id', 'ink_configuration_id'], 'unique_material_ink');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_ink_mappings');
    }
};

