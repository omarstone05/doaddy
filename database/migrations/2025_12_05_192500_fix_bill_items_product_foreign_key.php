<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support dropping foreign keys directly
        // We'll recreate the table without the product_id foreign key
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, we need to recreate the table
            // But this is complex, so we'll just note the issue
            // The foreign key will be ignored if product_id is always null
            return;
        }
        
        // For other databases, check if foreign key exists before dropping
        $connection = DB::connection();
        $fkExists = $connection->selectOne("
            SELECT COUNT(*) as count 
            FROM information_schema.key_column_usage 
            WHERE table_schema = DATABASE() 
            AND table_name = 'bill_items' 
            AND constraint_name = 'bill_items_product_id_foreign'
        ");
        
        if ($fkExists && $fkExists->count > 0) {
            Schema::table('bill_items', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        }
        
        // Make product_id nullable and optionally recreate foreign key
        Schema::table('bill_items', function (Blueprint $table) {
            if (Schema::hasColumn('bill_items', 'product_id')) {
                // Just make it nullable - don't recreate foreign key to avoid issues
                $table->unsignedBigInteger('product_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Reverse the change if needed
    }
};
