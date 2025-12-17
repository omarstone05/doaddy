<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The test suite uses SQLite; this migration is MySQL-specific, so skip in that environment.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (!Schema::hasTable('prospects')) {
            return;
        }

        // Check if id column is already UUID
        $columns = DB::select("SHOW COLUMNS FROM prospects WHERE Field = 'id'");
        if (empty($columns)) {
            return;
        }

        $idColumn = $columns[0];
        $isUuid = strpos(strtolower($idColumn->Type), 'char') !== false && strpos(strtolower($idColumn->Type), '36') !== false;

        if ($isUuid) {
            // Already UUID, nothing to do
            return;
        }

        // Step 1: Drop foreign keys that reference prospects.id FIRST
        $foreignKeys = DB::select("
            SELECT TABLE_NAME, CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME = 'prospects' 
            AND REFERENCED_COLUMN_NAME = 'id'
        ");

        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
        }

        // Step 2: Update quotations.prospect_id to UUID BEFORE changing prospects.id
        if (Schema::hasTable('quotations')) {
            try {
                // Check if prospect_id column exists
                $quotationsColumns = DB::select("SHOW COLUMNS FROM quotations WHERE Field = 'prospect_id'");
                if (!empty($quotationsColumns)) {
                    // Change prospect_id column to CHAR(36)
                    DB::statement('ALTER TABLE quotations MODIFY prospect_id CHAR(36) NULL');
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to update quotations.prospect_id', ['error' => $e->getMessage()]);
            }
        }

        // Step 3: Drop primary key if it exists
        try {
            $pkExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'prospects' 
                AND CONSTRAINT_TYPE = 'PRIMARY KEY'
            ");
            
            if (!empty($pkExists)) {
                DB::statement('ALTER TABLE prospects DROP PRIMARY KEY');
            }
        } catch (\Exception $e) {
            // Primary key might not exist
        }

        // Step 4: Change id column to UUID
        DB::statement('ALTER TABLE prospects MODIFY id CHAR(36) NOT NULL');

        // Step 5: Add primary key back only if it doesn't exist
        try {
            $pkExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'prospects' 
                AND CONSTRAINT_TYPE = 'PRIMARY KEY'
            ");
            
            if (empty($pkExists)) {
                DB::statement('ALTER TABLE prospects ADD PRIMARY KEY (id)');
            }
        } catch (\Exception $e) {
            // Primary key might already exist
        }

        // Step 6: Re-add foreign keys
        if (Schema::hasTable('quotations')) {
            try {
                $quotationsColumns = DB::select("SHOW COLUMNS FROM quotations WHERE Field = 'prospect_id'");
                if (!empty($quotationsColumns)) {
                    // Check if foreign key already exists
                    $existingFk = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'quotations' 
                        AND COLUMN_NAME = 'prospect_id'
                        AND REFERENCED_TABLE_NAME = 'prospects'
                    ");
                    
                    if (empty($existingFk)) {
                        DB::statement("
                            ALTER TABLE quotations 
                            ADD CONSTRAINT quotations_prospect_id_foreign 
                            FOREIGN KEY (prospect_id) REFERENCES prospects(id) ON DELETE SET NULL
                        ");
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to re-add quotations foreign key', ['error' => $e->getMessage()]);
            }
        }
    }

    public function down(): void
    {
        // Reverting would require converting UUIDs back to integers, which is not safe
        // This migration is one-way
    }
};
