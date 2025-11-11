<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('key_results')) {
            Schema::create('key_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('okr_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['number', 'percentage', 'currency', 'boolean'])->default('number');
            $table->decimal('target_value', 15, 2);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->string('unit')->nullable(); // e.g., "customers", "revenue", "%"
            $table->integer('progress_percentage')->default(0); // Calculated
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'at_risk'])->default('not_started');
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            // Foreign key for okr_id will be added after okrs table exists
            $table->index(['okr_id', 'display_order']);
            });
            
            // Add foreign key after okrs table exists
            if (Schema::hasTable('okrs')) {
                Schema::table('key_results', function (Blueprint $table) {
                    $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'key_results' AND COLUMN_NAME = 'okr_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                    if (empty($foreignKeys)) {
                        $table->foreign('okr_id')->references('id')->on('okrs')->onDelete('cascade');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_results');
    }
};
