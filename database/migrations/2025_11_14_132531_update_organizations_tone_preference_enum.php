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
        // Update the enum to include all valid values
        // SQLite doesn't support MODIFY COLUMN, so we skip this migration for SQLite
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE organizations MODIFY COLUMN tone_preference ENUM('professional', 'casual', 'motivational', 'sassy', 'technical', 'formal', 'conversational', 'friendly') DEFAULT 'professional'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the old enum values
        // SQLite doesn't support MODIFY COLUMN, so we skip this migration for SQLite
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE organizations MODIFY COLUMN tone_preference ENUM('formal', 'conversational', 'technical') DEFAULT 'formal'");
        }
    }
};
