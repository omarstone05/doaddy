<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ink_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->integer('bottles_per_set')->default(4);
            $table->decimal('cost_per_set', 15, 2);
            $table->decimal('coverage_area', 10, 2); // sqm per set
            $table->integer('coverage_multiplier')->default(1); // rolls covered per set
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('organization_id');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ink_configurations');
    }
};

