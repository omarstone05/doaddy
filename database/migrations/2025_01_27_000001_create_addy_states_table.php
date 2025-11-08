<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addy_states', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            
            $table->string('focus_area')->nullable();
            $table->decimal('urgency', 3, 2)->default(0);
            $table->text('context')->nullable();
            $table->string('mood')->default('neutral');
            $table->json('perception_data')->nullable();
            $table->json('priorities')->nullable();
            $table->timestamp('last_thought_cycle')->nullable();
            
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index('organization_id');
            $table->index('focus_area');
            $table->index('last_thought_cycle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addy_states');
    }
};

