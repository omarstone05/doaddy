<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable(); // Encrypted values
            $table->string('type')->default('string'); // string, boolean, json, encrypted
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default AI settings
        DB::table('platform_settings')->insert([
            [
                'key' => 'ai_provider',
                'value' => 'openai', // openai or anthropic
                'type' => 'string',
                'description' => 'AI provider for Addy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'openai_api_key',
                'value' => null,
                'type' => 'encrypted',
                'description' => 'OpenAI API key',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'openai_model',
                'value' => 'gpt-4o', // or gpt-4-turbo
                'type' => 'string',
                'description' => 'OpenAI model to use',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'anthropic_api_key',
                'value' => null,
                'type' => 'encrypted',
                'description' => 'Anthropic API key',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'anthropic_model',
                'value' => 'claude-sonnet-4-20250514',
                'type' => 'string',
                'description' => 'Anthropic model to use',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};

