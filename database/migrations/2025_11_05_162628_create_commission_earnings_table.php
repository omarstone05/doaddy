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
        if (!Schema::hasTable('commission_earnings')) {
            Schema::create('commission_earnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('team_member_id');
            $table->uuid('sale_id');
            $table->uuid('commission_rule_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('sale_amount', 12, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            // Foreign keys for team_member_id, sale_id, and commission_rule_id will be added after those tables exist
            $table->index(['organization_id', 'team_member_id', 'status']);
            $table->index(['sale_id']);
            });
            
            // Add foreign keys after referenced tables exist
            foreach (['team_members', 'sales', 'commission_rules'] as $refTable) {
                if (Schema::hasTable($refTable)) {
                    $column = match($refTable) {
                        'team_members' => 'team_member_id',
                        'sales' => 'sale_id',
                        'commission_rules' => 'commission_rule_id',
                    };
                    Schema::table('commission_earnings', function (Blueprint $table) use ($refTable, $column) {
                        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commission_earnings' AND COLUMN_NAME = '{$column}' AND REFERENCED_TABLE_NAME IS NOT NULL");
                        if (empty($foreignKeys)) {
                            $onDelete = $column === 'commission_rule_id' ? 'set null' : 'cascade';
                            $table->foreign($column)->references('id')->on($refTable)->onDelete($onDelete);
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
        Schema::dropIfExists('commission_earnings');
    }
};
