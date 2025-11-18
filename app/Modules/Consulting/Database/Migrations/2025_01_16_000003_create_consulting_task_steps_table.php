<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulting_task_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->uuid('completed_by')->nullable();
            $table->timestamps();
            
            $table->index('task_id');
            $table->index('is_completed');
            $table->index('completed_by');
            
            $table->foreign('task_id')
                  ->references('id')
                  ->on('consulting_tasks')
                  ->onDelete('cascade');
                  
            $table->foreign('completed_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_task_steps');
    }
};

