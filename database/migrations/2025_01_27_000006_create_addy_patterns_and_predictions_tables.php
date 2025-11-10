<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User patterns - learning behavior
        Schema::create('addy_user_patterns', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            
            // Weekly rhythm
            $table->json('weekly_rhythm')->nullable(); // Day preferences
            $table->json('peak_hours')->nullable(); // Best working hours
            $table->json('section_preferences')->nullable(); // Which sections used most
            
            // Behavior patterns
            $table->integer('avg_response_time')->default(0); // Minutes to respond to insights
            $table->json('action_patterns')->nullable(); // Which actions taken most
            $table->json('dismissed_insight_types')->nullable(); // What they ignore
            
            // Work style
            $table->string('work_style')->default('balanced'); // focused, balanced, creative
            $table->boolean('adhd_mode')->default(false);
            $table->integer('preferred_task_chunk_size')->default(3); // Tasks at once
            
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['organization_id', 'user_id']);
        });

        // Predictions - forecasting
        Schema::create('addy_predictions', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            
            $table->string('type'); // cash_flow, budget_burn, sales, inventory
            $table->string('category'); // money, sales, people, inventory
            $table->date('prediction_date'); // Date of prediction
            $table->date('target_date'); // Date being predicted
            
            $table->decimal('predicted_value', 15, 2)->nullable();
            $table->decimal('confidence', 3, 2)->default(0); // 0.0 to 1.0
            $table->json('factors')->nullable(); // What influenced prediction
            $table->json('metadata')->nullable();
            
            $table->decimal('actual_value', 15, 2)->nullable(); // Fill in later for accuracy
            $table->decimal('accuracy', 3, 2)->nullable(); // How accurate was prediction
            
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['organization_id', 'type', 'target_date']);
        });

        // Cultural settings - per organization
        Schema::create('addy_cultural_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            
            // Weekly rhythm customization
            $table->json('weekly_themes')->nullable(); // Monday: "Deep Work", etc.
            $table->json('blocked_times')->nullable(); // No notifications during these times
            $table->string('timezone')->default('UTC');
            
            // Communication preferences
            $table->string('tone')->default('professional'); // casual, professional, motivational
            $table->boolean('enable_predictions')->default(true);
            $table->boolean('enable_proactive_suggestions')->default(true);
            
            // Notification settings
            $table->integer('max_daily_suggestions')->default(5);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unique('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addy_predictions');
        Schema::dropIfExists('addy_user_patterns');
        Schema::dropIfExists('addy_cultural_settings');
    }
};

