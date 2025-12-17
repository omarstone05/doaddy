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
        if (!Schema::hasTable('commission_rules')) {
            Schema::create('commission_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('rule_type', ['percentage', 'fixed', 'tiered'])->default('percentage');
            $table->decimal('rate', 5, 2)->nullable(); // Percentage rate (e.g., 10.00 for 10%)
            $table->decimal('fixed_amount', 12, 2)->nullable(); // Fixed amount
            $table->json('tiers')->nullable(); // For tiered: [{min: 0, max: 1000, rate: 5}, ...]
            $table->enum('applicable_to', ['all', 'team_member', 'department'])->default('all');
            $table->uuid('team_member_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            // Foreign keys for team_member_id and department_id will be added after those tables exist
            $table->index(['organization_id', 'is_active']);
            });
        }

        if (Schema::hasTable('commission_rules') && Schema::hasTable('team_members')) {
            Schema::table('commission_rules', function (Blueprint $table) {
                $table->foreign('team_member_id')
                    ->references('id')
                    ->on('team_members')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('commission_rules') && Schema::hasTable('departments')) {
            Schema::table('commission_rules', function (Blueprint $table) {
                $table->foreign('department_id')
                    ->references('id')
                    ->on('departments')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
