<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name'); // e.g., "Starter", "Professional", "Enterprise"
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->string('currency', 3)->default('ZMW');
                $table->string('billing_period')->default('monthly'); // monthly, yearly
                $table->integer('trial_days')->default(14);
                $table->json('features')->nullable(); // Array of features
                $table->integer('max_users')->nullable();
                $table->integer('max_organizations')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};

