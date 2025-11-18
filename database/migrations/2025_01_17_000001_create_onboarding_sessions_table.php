<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('current_phase')->default('arrival');
            $table->json('data')->nullable(); // Stores all onboarding data
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'completed']);
            $table->index('current_phase');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_sessions');
    }
};

