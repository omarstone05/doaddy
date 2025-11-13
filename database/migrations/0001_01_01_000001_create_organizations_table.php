<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('business_type')->nullable();
            $table->string('industry')->nullable();
            $table->enum('tone_preference', [
                'professional',
                'casual',
                'motivational',
                'sassy',
                'technical',
                'formal',
                'conversational',
                'friendly',
            ])->default('professional');
            $table->string('currency', 3)->default('ZMW');
            $table->string('timezone')->default('Africa/Lusaka');
            $table->string('logo')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        
        // Add foreign key constraint to users table if it exists
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
