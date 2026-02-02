<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes the unique constraint on account_types.code to be 
     * a composite unique on (code, organization_id) instead of just code.
     * This allows different organizations to have the same account type codes.
     */
    public function up(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            // Drop the old unique constraint on just code
            $table->dropUnique('account_types_code_unique');
            
            // Add new composite unique constraint
            $table->unique(['code', 'organization_id'], 'account_types_code_org_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            // Drop the composite unique
            $table->dropUnique('account_types_code_org_unique');
            
            // Restore the original unique on just code
            $table->unique('code', 'account_types_code_unique');
        });
    }
};
