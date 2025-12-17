<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('budget_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('budget_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('budget_categories');
            $table->date('transaction_date');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3);
            $table->enum('transaction_type', ['expense', 'income'])->default('expense');
            $table->enum('source_app', ['addy', 'projjo', 'lide', 'manual'])->nullable();
            $table->string('source_id')->nullable()->index();
            $table->json('source_data')->nullable();
            $table->boolean('is_auto_categorized')->default(false);
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->boolean('category_overridden')->default(false);
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignUuid('reconciled_by')->nullable()->constrained('users');
            $table->text('receipt_url')->nullable();
            $table->json('receipt_data')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['budget_id', 'transaction_date']);
            $table->index(['source_app', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_transactions');
    }
};
