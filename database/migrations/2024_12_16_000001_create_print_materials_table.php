<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->enum('material_type', ['vinyl', 'banner', 'banner_flex', 'contra_vision', 'clear_vinyl', 'custom']);
            $table->decimal('roll_width', 10, 2); // in meters
            $table->decimal('roll_length', 10, 2); // in meters
            $table->decimal('material_cost', 15, 2);
            $table->decimal('off_cut_cost', 15, 2)->default(7.00);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'material_type']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_materials');
    }
};

