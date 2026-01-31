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
        // Add deleted_at to sales table if it exists and doesn't have the column
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'deleted_at')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at to sale_items table if it exists and doesn't have the column
        if (Schema::hasTable('sale_items') && !Schema::hasColumn('sale_items', 'deleted_at')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'deleted_at')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('sale_items') && Schema::hasColumn('sale_items', 'deleted_at')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
