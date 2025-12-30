<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('users', 'penda_account_id')) {
                $table->uuid('penda_account_id')->nullable()->after('id')->unique();
            }
            if (!Schema::hasColumn('users', 'penda_access_token')) {
                $table->text('penda_access_token')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'penda_refresh_token')) {
                $table->text('penda_refresh_token')->nullable()->after('penda_access_token');
            }
            if (!Schema::hasColumn('users', 'penda_token_expires_at')) {
                $table->timestamp('penda_token_expires_at')->nullable()->after('penda_refresh_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['penda_account_id', 'penda_access_token', 'penda_refresh_token', 'penda_token_expires_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};







