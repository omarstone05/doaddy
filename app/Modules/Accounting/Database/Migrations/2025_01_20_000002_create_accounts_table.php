<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->foreignUuid('account_type_id')->constrained('account_types')->onDelete('restrict');
            $table->uuid('parent_account_id')->nullable();
            $table->string('code', 50); // Account code (e.g., 1000, 1100, 1200)
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_sub_account')->default(false);
            $table->boolean('is_system_account')->default(false); // System accounts cannot be deleted
            $table->boolean('allows_postings')->default(true);
            $table->integer('level')->default(1); // Hierarchy level
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // Additional account settings
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('parent_account_id')->references('id')->on('accounts')->onDelete('restrict');
            
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'account_type_id', 'is_active']);
            $table->index(['parent_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

