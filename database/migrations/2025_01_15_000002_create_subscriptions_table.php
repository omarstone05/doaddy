<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id');
                $table->uuid('subscription_plan_id');
                $table->string('status')->default('pending'); // pending, active, cancelled, expired, past_due
                $table->string('lenco_reference')->nullable()->unique();
                $table->date('starts_at');
                $table->date('ends_at')->nullable();
                $table->date('trial_ends_at')->nullable();
                $table->date('cancelled_at')->nullable();
                $table->string('cancellation_reason')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('ZMW');
                $table->string('billing_period')->default('monthly');
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
                $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->onDelete('restrict');
                $table->index(['organization_id', 'status']);
                $table->index('lenco_reference');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

