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
        Schema::create('goal_milestones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('strategic_goal_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('target_date');
            $table->date('completed_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->foreign('strategic_goal_id')->references('id')->on('strategic_goals')->onDelete('cascade');
            $table->index(['strategic_goal_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_milestones');
    }
};
