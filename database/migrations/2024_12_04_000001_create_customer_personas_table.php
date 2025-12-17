<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_personas')) {
            // Table exists, add missing columns if any
            return;
        }

        Schema::create('customer_personas', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('industry')->nullable();
            $table->enum('size', ['small', 'medium', 'large', 'enterprise'])->default('small');
            $table->enum('payment_behavior', ['excellent', 'good', 'fair', 'poor'])->default('good');
            $table->json('attributes')->nullable(); // Custom attributes
            $table->string('color')->default('#14b8a6'); // Teal default
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_personas');
    }
};
