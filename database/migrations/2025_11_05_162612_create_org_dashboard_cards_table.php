<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_dashboard_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('dashboard_card_id');
            $table->json('config')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('dashboard_card_id')->references('id')->on('dashboard_cards')->onDelete('cascade');
            $table->unique(['organization_id', 'dashboard_card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_dashboard_cards');
    }
};
