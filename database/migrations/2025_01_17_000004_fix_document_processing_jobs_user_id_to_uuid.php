<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if table exists and column is integer
        if (Schema::hasTable('document_processing_jobs')) {
            Schema::table('document_processing_jobs', function (Blueprint $table) {
                // Drop foreign key if exists
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'document_processing_jobs' 
                    AND COLUMN_NAME = 'user_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($foreignKeys as $fk) {
                    try {
                        $table->dropForeign([$fk->CONSTRAINT_NAME]);
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                }
                
                // Drop indexes
                try {
                    $table->dropIndex('dpj_user_id_idx');
                    $table->dropIndex('dpj_org_user_created_idx');
                } catch (\Exception $e) {
                    // Indexes might not exist, continue
                }
            });
            
            // Change column type
            Schema::table('document_processing_jobs', function (Blueprint $table) {
                $table->uuid('user_id')->change();
            });
            
            // Re-add foreign key and indexes
            Schema::table('document_processing_jobs', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
                      
                $table->index(['organization_id', 'user_id', 'created_at'], 'dpj_org_user_created_idx');
                $table->index('user_id', 'dpj_user_id_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_processing_jobs')) {
            Schema::table('document_processing_jobs', function (Blueprint $table) {
                // Drop foreign key
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                
                $table->dropIndex('dpj_user_id_idx');
                $table->dropIndex('dpj_org_user_created_idx');
                
                // Change back to integer (but this will lose data if UUIDs exist)
                $table->unsignedBigInteger('user_id')->change();
                
                // Re-add indexes
                $table->index(['organization_id', 'user_id', 'created_at'], 'dpj_org_user_created_idx');
                $table->index('user_id', 'dpj_user_id_idx');
            });
        }
    }
};

