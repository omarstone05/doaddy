<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('budget_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type');
            $table->string('severity')->default('info');
            $table->string('title');
            $table->text('message');
            $table->integer('threshold_percentage')->nullable();
            $table->decimal('current_percentage', 8, 2)->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_alerts');
    }
};
