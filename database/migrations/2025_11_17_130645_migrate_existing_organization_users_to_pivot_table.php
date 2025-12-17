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
        // Migrate existing organization_id relationships to pivot table
        $users = DB::table('users')
            ->whereNotNull('organization_id')
            ->get();
        
        foreach ($users as $user) {
            // Check if relationship already exists in pivot table
            $exists = DB::table('organization_user')
                ->where('user_id', $user->id)
                ->where('organization_id', $user->organization_id)
                ->exists();
            
            if (!$exists) {
                DB::table('organization_user')->insert([
                    'user_id' => $user->id,
                    'organization_id' => $user->organization_id,
                    'role' => 'owner', // Assume existing users are owners
                    'is_active' => true,
                    'joined_at' => $user->created_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore organization_id from pivot table (use first organization)
        $pivotRecords = DB::table('organization_user')
            ->orderBy('joined_at', 'asc')
            ->get()
            ->groupBy('user_id');
        
        foreach ($pivotRecords as $userId => $records) {
            $firstOrg = $records->first();
            DB::table('users')
                ->where('id', $userId)
                ->update(['organization_id' => $firstOrg->organization_id]);
        }
    }
};
