<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the neuro_storage table for NeuroCore persistence
 * This table stores user profiles, conversation history, goals, etc.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('neuro_storage', function (Blueprint $table) {
            $table->id();
            $table->string('key', 500)->unique();
            $table->longText('value');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('key');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neuro_storage');
    }
};


