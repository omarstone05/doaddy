<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_dashboard_cards', function (Blueprint $table) {
            $table->integer('row')->default(0)->after('display_order');
            $table->integer('col')->default(0)->after('row');
            $table->integer('width')->default(4)->after('col'); // Grid units (1-12)
            $table->integer('height')->default(3)->after('width'); // Grid units (rows)
        });
    }

    public function down(): void
    {
        Schema::table('org_dashboard_cards', function (Blueprint $table) {
            $table->dropColumn(['row', 'col', 'width', 'height']);
        });
    }
};
