<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedInteger('follow_up_count')->default(0)->after('status');
            $table->timestamp('last_follow_up_at')->nullable()->after('follow_up_count');
            $table->string('last_follow_up_method')->nullable()->after('last_follow_up_at');
            $table->text('last_follow_up_notes')->nullable()->after('last_follow_up_method');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'follow_up_count',
                'last_follow_up_at',
                'last_follow_up_method',
                'last_follow_up_notes',
            ]);
        });
    }
};
