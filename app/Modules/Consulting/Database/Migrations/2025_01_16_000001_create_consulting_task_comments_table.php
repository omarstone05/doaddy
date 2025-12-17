<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulting_task_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->uuid('user_id'); // Comment author
            $table->uuid('parent_comment_id')->nullable(); // For replies
            $table->text('comment');
            $table->boolean('is_internal')->default(false); // Internal notes vs client-visible
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('task_id');
            $table->index('user_id');
            $table->index('parent_comment_id');
            
            $table->foreign('task_id')
                  ->references('id')
                  ->on('consulting_tasks')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->foreign('parent_comment_id')
                  ->references('id')
                  ->on('consulting_task_comments')
                  ->onDelete('cascade');
        });

        Schema::create('consulting_task_comment_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('comment_id');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->uuid('uploaded_by');
            $table->timestamps();
            
            $table->index('comment_id');
            $table->index('uploaded_by');
            
            $table->foreign('comment_id')
                  ->references('id')
                  ->on('consulting_task_comments')
                  ->onDelete('cascade');
                  
            $table->foreign('uploaded_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_task_comment_attachments');
        Schema::dropIfExists('consulting_task_comments');
    }
};

