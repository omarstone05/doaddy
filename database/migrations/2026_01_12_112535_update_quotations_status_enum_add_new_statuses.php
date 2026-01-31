<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite (used in tests) does not support MODIFY/ENUM; skip safely.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Modify the status enum to include new statuses: draft, approved, sent, complete
        // Keep existing statuses for backward compatibility
        DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('draft', 'approved', 'sent', 'complete', 'viewed', 'accepted', 'rejected', 'expired') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Revert to original enum values
        DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired') DEFAULT 'draft'");
    }
};
