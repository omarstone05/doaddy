<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('license_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // e.g., "business", "professional", "trade"
            $table->string('issuing_authority');
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->enum('status', ['active', 'expired', 'pending_renewal', 'suspended'])->default('active');
            $table->boolean('is_renewable')->default(true);
            $table->date('renewal_date')->nullable(); // When to renew (before expiry)
            $table->text('notes')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['organization_id', 'status']);
            $table->index(['expiry_date']);
            $table->index(['renewal_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
