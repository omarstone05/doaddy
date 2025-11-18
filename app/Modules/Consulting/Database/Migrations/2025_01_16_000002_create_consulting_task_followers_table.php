<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulting_task_followers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->uuid('user_id');
            $table->timestamps();
            
            $table->unique(['task_id', 'user_id']);
            $table->index('task_id');
            $table->index('user_id');
            
            $table->foreign('task_id')
                  ->references('id')
                  ->on('consulting_tasks')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_task_followers');
    }
};

