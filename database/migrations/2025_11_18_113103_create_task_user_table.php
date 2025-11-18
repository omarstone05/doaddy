<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_user', function (Blueprint $table) {
            $table->id();
            $table->uuid('task_id');
            $table->uuid('user_id');
            $table->uuid('assigned_by_id')->nullable();
            $table->boolean('can_edit')->default(true);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_assign')->default(false);
            $table->boolean('can_view_time')->default(true);
            $table->boolean('can_manage_subtasks')->default(false);
            $table->boolean('can_change_status')->default(true);
            $table->boolean('can_change_priority')->default(false);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            
            $table->foreign('task_id')->references('id')->on('project_tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_by_id')->references('id')->on('users')->onDelete('set null');
            
            // A user can be assigned to a task only once
            $table->unique(['task_id', 'user_id']);
            $table->index(['user_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_user');
    }
};
