<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('organization_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'review', 'done', 'blocked'])->default('todo');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->uuid('assigned_to_id')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->date('due_date')->nullable();
            $table->date('start_date')->nullable();
            $table->integer('estimated_hours')->nullable();
            $table->integer('actual_hours')->nullable();
            $table->integer('order')->default(0);
            $table->uuid('parent_task_id')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_task_id')->references('id')->on('project_tasks')->onDelete('cascade');
            $table->index(['project_id', 'status']);
            $table->index(['organization_id', 'assigned_to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};

