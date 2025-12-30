<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * These tables cache Penda Cloud data locally for performance
     */
    public function up(): void
    {
        // Local cache of organization subscriptions from Penda Cloud
        if (!Schema::hasTable('organization_subscriptions')) {
            Schema::create('organization_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id');
                $table->string('app_id', 50)->default('addy');
                $table->string('plan', 50); // free, starter, professional, enterprise
                $table->string('status', 20)->default('active'); // active, trial, cancelled, expired
                $table->boolean('has_elective_modules')->default(false);
                $table->boolean('has_custom_module')->default(false);
                $table->integer('max_users')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('synced_at')->nullable(); // Last sync from Penda Cloud
                $table->timestamps();
                
                $table->unique(['organization_id', 'app_id']);
                $table->index('status');
            });
        }

        // Local cache of module access from Penda Cloud
        if (!Schema::hasTable('organization_modules')) {
            Schema::create('organization_modules', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id');
                $table->string('module_slug', 50);
                $table->string('access_type', 20)->default('subscription'); // subscription, addon, custom, granted
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
                
                $table->unique(['organization_id', 'module_slug']);
            });
        }

        // Local modules registry (synced from Penda Cloud)
        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->unique();
                $table->string('name');
                $table->string('type', 20); // core, elective, custom
                $table->text('description')->nullable();
                $table->string('icon', 50)->nullable();
                $table->decimal('price_monthly', 10, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Organization branding cache (synced from Penda Cloud)
        if (!Schema::hasTable('organization_branding_cache')) {
            Schema::create('organization_branding_cache', function (Blueprint $table) {
                $table->uuid('organization_id')->primary();
                $table->string('name')->nullable();
                $table->string('logo')->nullable();
                $table->string('logo_light')->nullable();
                $table->string('logo_dark')->nullable();
                $table->string('favicon')->nullable();
                $table->string('primary_color', 7)->nullable();
                $table->string('secondary_color', 7)->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('website')->nullable();
                $table->text('address')->nullable();
                $table->string('tax_id')->nullable();
                $table->string('registration_number')->nullable();
                $table->json('social_links')->nullable();
                $table->timestamp('synced_at');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_branding_cache');
        Schema::dropIfExists('organization_modules');
        Schema::dropIfExists('organization_subscriptions');
        Schema::dropIfExists('modules');
    }
};







