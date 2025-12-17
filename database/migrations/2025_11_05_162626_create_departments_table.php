<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        }

        if (Schema::hasTable('departments') && Schema::hasTable('team_members')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreign('manager_id')
                    ->references('id')
                    ->on('team_members')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
