<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'penda_account_id')) {
                $table->string('penda_account_id')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'penda_access_token')) {
                $table->text('penda_access_token')->nullable();
            }
            if (!Schema::hasColumn('users', 'penda_refresh_token')) {
                $table->text('penda_refresh_token')->nullable();
            }
            if (!Schema::hasColumn('users', 'penda_token_expires_at')) {
                $table->timestamp('penda_token_expires_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['penda_account_id', 'penda_access_token', 'penda_refresh_token', 'penda_token_expires_at', 'last_login_ip', 'last_login_at']);
        });
    }
};
