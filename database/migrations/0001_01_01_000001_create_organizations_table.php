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
            $table->enum('tone_preference', ['formal', 'conversational', 'technical'])->default('conversational');
            $table->string('currency', 3)->default('ZMW');
            $table->string('timezone')->default('Africa/Lusaka');
            $table->string('logo')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
