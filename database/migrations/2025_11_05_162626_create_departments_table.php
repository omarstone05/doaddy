<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->uuid('manager_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
                // Foreign key for manager_id will be added after team_members table exists
                $table->index(['organization_id', 'is_active']);
            });
            
            // Add foreign key after team_members table exists
            if (Schema::hasTable('team_members')) {
                Schema::table('departments', function (Blueprint $table) {
                    $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'departments' AND COLUMN_NAME = 'manager_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                    if (empty($foreignKeys)) {
                        $table->foreign('manager_id')->references('id')->on('team_members')->onDelete('set null');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
