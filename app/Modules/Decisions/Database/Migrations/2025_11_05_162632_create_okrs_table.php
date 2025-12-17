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
        if (!Schema::hasTable('okrs')) {
            Schema::create('okrs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('quarter'); // e.g., "2024-Q1"
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->uuid('owner_id')->nullable(); // Team member or user
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('progress_percentage')->default(0); // Calculated from key results
            $table->text('notes')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            // Foreign keys for owner_id and created_by_id will be added after users table exists
            $table->index(['organization_id', 'quarter', 'status']);
            });
        }

        if (Schema::hasTable('okrs') && Schema::hasTable('users')) {
            Schema::table('okrs', function (Blueprint $table) {
                foreach (['owner_id', 'created_by_id'] as $column) {
                    $table->foreign($column)
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okrs');
    }
};
