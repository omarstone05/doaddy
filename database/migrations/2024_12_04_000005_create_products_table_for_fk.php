<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates a minimal products table for SQLite compatibility.
 * The quotation_items table has a FK to "products" (from original migration).
 * The fix migration (2025_12_16) updates this to goods_and_services but skips SQLite.
 * This migration ensures SQLite tests can run by providing the expected table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        // Only create for SQLite (testing) - MySQL/production use goods_and_services
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('products');
        }
    }
};
