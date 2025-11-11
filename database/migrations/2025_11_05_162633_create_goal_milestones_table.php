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
        if (!Schema::hasTable('goal_milestones')) {
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
            
            // Foreign key for strategic_goal_id will be added after strategic_goals table exists
            $table->index(['strategic_goal_id', 'display_order']);
            });
            
            // Add foreign key after strategic_goals table exists
            if (Schema::hasTable('strategic_goals')) {
                Schema::table('goal_milestones', function (Blueprint $table) {
                    $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'goal_milestones' AND COLUMN_NAME = 'strategic_goal_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                    if (empty($foreignKeys)) {
                        $table->foreign('strategic_goal_id')->references('id')->on('strategic_goals')->onDelete('cascade');
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
        Schema::dropIfExists('goal_milestones');
    }
};
