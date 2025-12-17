<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support dropping foreign keys easily
            // The foreign key constraint is checked but not enforced in SQLite
            // So we can just skip this migration for SQLite
            return;
        }
        
        // For MySQL/PostgreSQL, drop and recreate the foreign key
        Schema::table('quotation_items', function (Blueprint $table) {
            // Try to drop existing foreign key (constraint name may vary)
            try {
                // Common constraint name pattern
                $constraintName = 'quotation_items_product_id_foreign';
                DB::statement("ALTER TABLE quotation_items DROP FOREIGN KEY {$constraintName}");
            } catch (\Exception $e) {
                // Try alternative constraint names
                try {
                    $constraints = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'quotation_items' 
                        AND COLUMN_NAME = 'product_id'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    
                    if (!empty($constraints)) {
                        foreach ($constraints as $constraint) {
                            DB::statement("ALTER TABLE quotation_items DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                        }
                    }
                } catch (\Exception $e2) {
                    // Constraint might not exist, continue
                }
            }
        });

        // Change product_id to uuid type to match goods_and_services.id
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->uuid('product_id')->nullable()->change();
        });

        // Recreate the foreign key pointing to goods_and_services
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->foreign('product_id')
                  ->references('id')
                  ->on('goods_and_services')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            return;
        }
        
        Schema::table('quotation_items', function (Blueprint $table) {
            try {
                $constraintName = 'quotation_items_product_id_foreign';
                DB::statement("ALTER TABLE quotation_items DROP FOREIGN KEY {$constraintName}");
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }
        });
    }
};

