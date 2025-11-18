<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if table exists and column is integer
        if (Schema::hasTable('uploaded_files')) {
            Schema::table('uploaded_files', function (Blueprint $table) {
                // Drop foreign key if exists
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'uploaded_files' 
                    AND COLUMN_NAME = 'user_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign([$fk->CONSTRAINT_NAME]);
                }
                
                // Drop indexes
                $table->dropIndex('uf_user_id_idx');
                $table->dropIndex('uf_org_user_created_idx');
            });
            
            // Change column type
            Schema::table('uploaded_files', function (Blueprint $table) {
                $table->uuid('user_id')->change();
            });
            
            // Re-add foreign key and indexes
            Schema::table('uploaded_files', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
                      
                $table->index(['organization_id', 'user_id', 'created_at'], 'uf_org_user_created_idx');
                $table->index('user_id', 'uf_user_id_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('uploaded_files')) {
            Schema::table('uploaded_files', function (Blueprint $table) {
                // Drop foreign key
                $table->dropForeign(['user_id']);
                $table->dropIndex('uf_user_id_idx');
                $table->dropIndex('uf_org_user_created_idx');
                
                // Change back to integer (but this will lose data if UUIDs exist)
                $table->unsignedBigInteger('user_id')->change();
                
                // Re-add indexes
                $table->index(['organization_id', 'user_id', 'created_at'], 'uf_org_user_created_idx');
                $table->index('user_id', 'uf_user_id_idx');
            });
        }
    }
};

