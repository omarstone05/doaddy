<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Invoice/Quote settings will be stored in the existing 'settings' JSON column
            // No new columns needed - we'll use the settings JSON field
            // This migration is here for documentation purposes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to rollback - using existing settings column
    }
};
