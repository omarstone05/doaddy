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
        Schema::table('account_types', function (Blueprint $table) {
            // Add report_category to determine which financial report this account type appears in
            $table->enum('report_category', [
                'balance_sheet', 
                'profit_loss', 
                'cash_flow'
            ])->nullable()->after('normal_balance');
        });
        
        // Set report_category based on category
        \DB::statement("
            UPDATE account_types 
            SET report_category = CASE 
                WHEN category IN ('asset', 'liability', 'equity') THEN 'balance_sheet'
                WHEN category IN ('revenue', 'expense') THEN 'profit_loss'
                ELSE NULL
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropColumn('report_category');
        });
    }
};
