<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'last_reminder_sent_at')) {
                $table->timestamp('last_reminder_sent_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('invoices', 'reminder_count')) {
                $table->unsignedInteger('reminder_count')->default(0)->after('last_reminder_sent_at');
            }

            if (!Schema::hasColumn('invoices', 'last_reminder_channel')) {
                $table->string('last_reminder_channel')->nullable()->after('reminder_count');
            }

            if (!Schema::hasColumn('invoices', 'last_reminder_notes')) {
                $table->text('last_reminder_notes')->nullable()->after('last_reminder_channel');
            }

            if (!Schema::hasColumn('invoices', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('paid_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'last_reminder_sent_at',
                'reminder_count',
                'last_reminder_channel',
                'last_reminder_notes',
                'paid_at',
            ]);
        });
    }
};
