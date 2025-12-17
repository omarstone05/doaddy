<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('budget_categories');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('item_code', 50)->nullable();
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->enum('item_type', ['expense', 'income'])->default('expense');
            $table->enum('frequency', ['one_time', 'recurring_monthly', 'recurring_quarterly', 'recurring_annual'])->default('one_time');
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
