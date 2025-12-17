<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'payment_details')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->json('payment_details')->nullable()->after('terms');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'payment_details')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('payment_details');
            });
        }
    }
};


