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
        // Add role_id column (nullable first, we'll populate it)
        Schema::table('organization_user', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('organization_roles')->onDelete('restrict');
            $table->index('role_id');
        });

        // Migrate existing role strings to role_ids
        // Note: OrganizationRoleSeeder should be run first to create default roles
        $roles = DB::table('organization_roles')->pluck('id', 'slug')->toArray();
        
        if (!empty($roles)) {
            // Map old role strings to new role_ids
            if (isset($roles['owner'])) {
                DB::table('organization_user')
                    ->where('role', 'owner')
                    ->whereNull('role_id')
                    ->update(['role_id' => $roles['owner']]);
            }
            
            if (isset($roles['admin'])) {
                DB::table('organization_user')
                    ->where('role', 'admin')
                    ->whereNull('role_id')
                    ->update(['role_id' => $roles['admin']]);
            }
            
            if (isset($roles['member'])) {
                DB::table('organization_user')
                    ->where('role', 'member')
                    ->whereNull('role_id')
                    ->update(['role_id' => $roles['member']]);
                
                // Set default to member if role_id is still null
                DB::table('organization_user')
                    ->whereNull('role_id')
                    ->update(['role_id' => $roles['member']]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
