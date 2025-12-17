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
        if (!Schema::hasTable('key_result_check_ins')) {
            Schema::create('key_result_check_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('key_result_id');
            $table->decimal('current_value', 15, 2);
            $table->integer('progress_percentage');
            $table->text('notes')->nullable();
            $table->enum('confidence', ['low', 'medium', 'high'])->default('medium');
            $table->uuid('checked_in_by_id')->nullable();
            $table->timestamps();
            
            // Foreign keys will be added after referenced tables exist
            $table->index(['key_result_id', 'created_at']);
            });
        }

        if (Schema::hasTable('key_result_check_ins') && Schema::hasTable('key_results')) {
            Schema::table('key_result_check_ins', function (Blueprint $table) {
                $table->foreign('key_result_id')
                    ->references('id')
                    ->on('key_results')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('key_result_check_ins') && Schema::hasTable('users')) {
            Schema::table('key_result_check_ins', function (Blueprint $table) {
                $table->foreign('checked_in_by_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_result_check_ins');
    }
};
