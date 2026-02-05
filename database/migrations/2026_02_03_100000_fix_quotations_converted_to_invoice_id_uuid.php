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

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['converted_to_invoice_id']);
        });

        // Clear any invalid bigint values (invoices use UUID, old values would be wrong)
        DB::table('quotations')->whereNotNull('converted_to_invoice_id')->update(['converted_to_invoice_id' => null]);

        DB::statement('ALTER TABLE quotations MODIFY converted_to_invoice_id CHAR(36) NULL');

        Schema::table('quotations', function (Blueprint $table) {
            $table->foreign('converted_to_invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('quotations') || DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['converted_to_invoice_id']);
        });

        DB::statement('ALTER TABLE quotations MODIFY converted_to_invoice_id BIGINT UNSIGNED NULL');

        Schema::table('quotations', function (Blueprint $table) {
            $table->foreign('converted_to_invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }
};
