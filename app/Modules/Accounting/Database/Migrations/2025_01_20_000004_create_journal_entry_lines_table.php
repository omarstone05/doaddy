<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignUuid('account_id')->constrained('accounts')->onDelete('restrict');
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->integer('line_number')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            
            $table->index(['journal_entry_id', 'line_number']);
            $table->index(['account_id', 'journal_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};

