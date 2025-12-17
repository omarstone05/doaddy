<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('budget_id')->constrained()->cascadeOnDelete();
            $table->string('insight_type');
            $table->string('severity')->default('info');
            $table->string('title');
            $table->text('description');
            $table->string('ai_model')->nullable();
            $table->unsignedTinyInteger('confidence_score')->nullable();
            $table->json('recommendations')->nullable();
            $table->boolean('is_dismissed')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_insights');
    }
};
