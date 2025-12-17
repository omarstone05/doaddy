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
            $table->enum('type', ['company', 'project', 'personal']);
            $table->enum('parent_app', ['addy', 'projjo', 'lide']);
            $table->string('parent_id')->index();
            $table->string('currency_code', 3)->default('ZMW');
            $table->string('timezone', 50)->default('Africa/Lusaka');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_app', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
