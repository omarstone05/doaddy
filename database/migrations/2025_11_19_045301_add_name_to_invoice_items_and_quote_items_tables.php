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
        // Add name column to invoice_items
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->string('name')->nullable()->after('goods_service_id');
            });
            
            // Migrate existing data: set name = description for existing items
            DB::table('invoice_items')->update([
                'name' => DB::raw('description')
            ]);
            
            // Make name required after migration
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->string('name')->nullable(false)->change();
            });
            
            // Make description nullable since it's now optional
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            });
        }
        
        // Add name column to quote_items
        if (Schema::hasTable('quote_items')) {
            Schema::table('quote_items', function (Blueprint $table) {
                $table->string('name')->nullable()->after('goods_service_id');
            });
            
            // Migrate existing data: set name = description for existing items
            DB::table('quote_items')->update([
                'name' => DB::raw('description')
            ]);
            
            // Make name required after migration
            Schema::table('quote_items', function (Blueprint $table) {
                $table->string('name')->nullable(false)->change();
            });
            
            // Make description nullable since it's now optional
            Schema::table('quote_items', function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn('name');
                $table->string('description')->nullable(false)->change();
            });
        }
        
        if (Schema::hasTable('quote_items')) {
            Schema::table('quote_items', function (Blueprint $table) {
                $table->dropColumn('name');
                $table->string('description')->nullable(false)->change();
            });
        }
    }
};
