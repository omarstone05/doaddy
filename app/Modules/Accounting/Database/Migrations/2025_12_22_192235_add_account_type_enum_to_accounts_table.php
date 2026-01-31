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
        Schema::table('accounts', function (Blueprint $table) {
            // Add account_type enum for easier filtering (derived from account_type_id relationship)
            // This is a denormalized field for performance
            if (!Schema::hasColumn('accounts', 'account_type')) {
                $table->enum('account_type', [
                    'asset', 
                    'liability', 
                    'equity', 
                    'income', 
                    'expense', 
                    'other_income', 
                    'cost_of_goods_sold'
                ])->nullable()->after('account_type_id');
            }
        });
        
        // Add index (will fail silently if it already exists)
        try {
            Schema::table('accounts', function (Blueprint $table) {
                $table->index(['organization_id', 'account_type', 'is_active'], 'accounts_org_type_active_idx');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
        
        // Populate account_type from account_type_id relationship
        // Use SQLite-compatible syntax
        $driver = \DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            \DB::statement("
                UPDATE accounts 
                SET account_type = (
                    SELECT CASE 
                        WHEN at.category = 'asset' THEN 'asset'
                        WHEN at.category = 'liability' THEN 'liability'
                        WHEN at.category = 'equity' THEN 'equity'
                        WHEN at.category = 'revenue' THEN 'income'
                        WHEN at.category = 'expense' THEN 'expense'
                        ELSE NULL
                    END
                    FROM account_types at
                    WHERE at.id = accounts.account_type_id
                )
            ");
        } else {
            \DB::statement("
                UPDATE accounts a
                INNER JOIN account_types at ON a.account_type_id = at.id
                SET a.account_type = CASE 
                    WHEN at.category = 'asset' THEN 'asset'
                    WHEN at.category = 'liability' THEN 'liability'
                    WHEN at.category = 'equity' THEN 'equity'
                    WHEN at.category = 'revenue' THEN 'income'
                    WHEN at.category = 'expense' THEN 'expense'
                    ELSE NULL
                END
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'account_type', 'is_active']);
            $table->dropColumn('account_type');
        });
    }
};
