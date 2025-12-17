<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('money_movements', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('status');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->uuid('verified_by_id')->nullable()->after('verified_at');
            
            $table->index(['organization_id', 'is_verified']);
        });
    }

    public function down(): void
    {
        Schema::table('money_movements', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'is_verified']);
            $table->dropColumn(['is_verified', 'verified_at', 'verified_by_id']);
        });
    }
};
