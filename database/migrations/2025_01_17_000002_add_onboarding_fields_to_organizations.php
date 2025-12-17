<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Business details from onboarding
            if (!Schema::hasColumn('organizations', 'business_description')) {
                $table->text('business_description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('organizations', 'business_category')) {
                $table->string('business_category')->nullable()->after('business_description');
            }
            if (!Schema::hasColumn('organizations', 'team_size')) {
                $table->string('team_size')->nullable()->after('business_category');
            }
            if (!Schema::hasColumn('organizations', 'income_pattern')) {
                $table->string('income_pattern')->nullable()->after('team_size');
            }
            
            // Configuration
            if (!Schema::hasColumn('organizations', 'priorities')) {
                $table->json('priorities')->nullable()->after('income_pattern');
            }
            if (!Schema::hasColumn('organizations', 'enabled_modules')) {
                $table->json('enabled_modules')->nullable()->after('priorities');
            }
            if (!Schema::hasColumn('organizations', 'recommended_dashboard_cards')) {
                $table->json('recommended_dashboard_cards')->nullable()->after('enabled_modules');
            }
            if (!Schema::hasColumn('organizations', 'default_monthly_budget')) {
                $table->integer('default_monthly_budget')->nullable()->after('recommended_dashboard_cards');
            }
            
            // Timestamps
            if (!Schema::hasColumn('organizations', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('default_monthly_budget');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $columns = [
                'business_description',
                'business_category',
                'team_size',
                'income_pattern',
                'priorities',
                'enabled_modules',
                'recommended_dashboard_cards',
                'default_monthly_budget',
                'onboarding_completed_at',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('organizations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

