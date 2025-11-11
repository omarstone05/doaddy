<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('addy_chat_messages')) {
            Schema::table('addy_chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('addy_chat_messages', 'attachments')) {
                    $table->json('attachments')->nullable()->after('metadata');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('addy_chat_messages')) {
            Schema::table('addy_chat_messages', function (Blueprint $table) {
                if (Schema::hasColumn('addy_chat_messages', 'attachments')) {
                    $table->dropColumn('attachments');
                }
            });
        }
    }
};

