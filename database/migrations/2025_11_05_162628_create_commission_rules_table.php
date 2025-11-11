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
            
            // Add foreign keys after referenced tables exist
            foreach (['team_members', 'departments'] as $refTable) {
                if (Schema::hasTable($refTable)) {
                    $column = match($refTable) {
                        'team_members' => 'team_member_id',
                        'departments' => 'department_id',
                    };
                    Schema::table('commission_rules', function (Blueprint $table) use ($refTable, $column) {
                        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commission_rules' AND COLUMN_NAME = '{$column}' AND REFERENCED_TABLE_NAME IS NOT NULL");
                        if (empty($foreignKeys)) {
                            $table->foreign($column)->references('id')->on($refTable)->onDelete('cascade');
                        }
                    });
                }
            }
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
