<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addy_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->string('role'); // 'user' or 'assistant'
            $table->text('content');
            $table->json('metadata')->nullable(); // command info, actions, etc.
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['organization_id', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addy_chat_messages');
    }
};

