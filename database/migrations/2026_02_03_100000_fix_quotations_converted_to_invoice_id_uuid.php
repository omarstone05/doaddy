<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix converted_to_invoice_id: invoices use UUID, quotations had bigint.
     */
    public function up(): void
    {
        if (!Schema::hasTable('quotations') || !Schema::hasColumn('quotations', 'converted_to_invoice_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Only drop foreign key if it exists (production may not have it)
        $fkName = $this->getForeignKeyName('quotations', 'converted_to_invoice_id');
        if ($fkName) {
            Schema::table('quotations', function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        }

        // Clear any invalid bigint values (invoices use UUID, old values would be wrong)
        DB::table('quotations')->whereNotNull('converted_to_invoice_id')->update(['converted_to_invoice_id' => null]);

        DB::statement('ALTER TABLE quotations MODIFY converted_to_invoice_id CHAR(36) NULL');

        // Only add foreign key if it doesn't already exist
        if (!$this->getForeignKeyName('quotations', 'converted_to_invoice_id')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->foreign('converted_to_invoice_id')->references('id')->on('invoices')->nullOnDelete();
            });
        }
    }

    /**
     * Get the foreign key constraint name for a column, or null if none exists.
     */
    private function getForeignKeyName(string $table, string $column): ?string
    {
        $result = DB::selectOne("
            SELECT CONSTRAINT_NAME as name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

        return $result?->name;
    }

    public function down(): void
    {
        if (!Schema::hasTable('quotations') || DB::getDriverName() === 'sqlite') {
            return;
        }

        $fkName = $this->getForeignKeyName('quotations', 'converted_to_invoice_id');
        if ($fkName) {
            Schema::table('quotations', function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        }

        DB::statement('ALTER TABLE quotations MODIFY converted_to_invoice_id BIGINT UNSIGNED NULL');

        Schema::table('quotations', function (Blueprint $table) {
            $table->foreign('converted_to_invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }
};
