<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addy_insights', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->foreignId('addy_state_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('type');
            $table->string('category');
            $table->string('title');
            $table->text('description');
            $table->decimal('priority', 3, 2)->default(0.5);
            $table->boolean('is_actionable')->default(false);
            $table->json('suggested_actions')->nullable();
            $table->string('action_url')->nullable();
            $table->string('status')->default('active');
            
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index('organization_id');
            $table->index('category');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addy_insights');
    }
};

