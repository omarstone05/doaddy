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
        Schema::create('user_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->date('date');
            $table->string('metric_type'); // e.g., 'login', 'session_duration', 'page_views', 'actions', 'feature_usage'
            $table->integer('value')->default(0);
            $table->json('metadata')->nullable(); // Additional context like feature name, page path, etc.
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'date', 'metric_type']);
            $table->index('date');
            $table->index('metric_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_metrics');
    }
};
