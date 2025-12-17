<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gamification XP Records
        if (Schema::hasTable('gamification_xp')) {
            return; // Tables already exist
        }
        
        Schema::create('gamification_xp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('organization_id')->nullable();
            $table->integer('xp_amount');
            $table->string('reason'); // sale_recorded, expense_tracked, etc.
            $table->json('context')->nullable(); // Additional context data
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
        });

        // Gamification Badges
        Schema::create('gamification_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('organization_id')->nullable();
            $table->string('badge_type'); // first_sale, sales_warrior, week_warrior, etc.
            $table->string('badge_name');
            $table->timestamp('earned_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unique(['user_id', 'badge_type']);
        });

        // Gamification Streaks
        Schema::create('gamification_streaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('organization_id')->nullable();
            $table->string('streak_type')->default('daily'); // daily, weekly
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->boolean('weekend_pause_enabled')->default(true);
            $table->integer('streak_freezes_remaining')->default(1);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unique(['user_id', 'streak_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_streaks');
        Schema::dropIfExists('gamification_badges');
        Schema::dropIfExists('gamification_xp');
    }
};

