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
        if (!Schema::hasTable('strategic_goals')) {
            Schema::create('strategic_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->date('target_date');
            $table->integer('progress_percentage')->default(0); // Calculated from milestones
            $table->text('notes')->nullable();
            $table->uuid('owner_id')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            // Foreign keys for owner_id and created_by_id will be added after users table exists
            $table->index(['organization_id', 'status']);
            });
            
            // Add foreign keys after users table exists
            if (Schema::hasTable('users')) {
                Schema::table('strategic_goals', function (Blueprint $table) {
                    foreach (['owner_id', 'created_by_id'] as $column) {
                        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'strategic_goals' AND COLUMN_NAME = '{$column}' AND REFERENCED_TABLE_NAME IS NOT NULL");
                        if (empty($foreignKeys)) {
                            $table->foreign($column)->references('id')->on('users')->onDelete('set null');
                        }
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
        Schema::dropIfExists('strategic_goals');
    }
};
