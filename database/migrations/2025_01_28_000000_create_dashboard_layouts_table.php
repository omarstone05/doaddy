<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('organization_id');
            $table->json('layout'); // Stores the complete layout structure
            $table->timestamps();

            // User can only have one layout per organization
            $table->unique(['user_id', 'organization_id']);
            
            // Indexes
            $table->index('user_id');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_layouts');
    }
};

