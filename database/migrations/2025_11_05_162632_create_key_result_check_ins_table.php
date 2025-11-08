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
        Schema::create('key_result_check_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('key_result_id');
            $table->decimal('current_value', 15, 2);
            $table->integer('progress_percentage');
            $table->text('notes')->nullable();
            $table->enum('confidence', ['low', 'medium', 'high'])->default('medium');
            $table->uuid('checked_in_by_id')->nullable();
            $table->timestamps();
            
            $table->foreign('key_result_id')->references('id')->on('key_results')->onDelete('cascade');
            $table->foreign('checked_in_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['key_result_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_result_check_ins');
    }
};
