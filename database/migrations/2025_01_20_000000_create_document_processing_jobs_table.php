<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_processing_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            
            // Status tracking
            $table->enum('status', [
                'pending',
                'extracting',
                'analyzing',
                'validating',
                'fixing',
                'analyzing_confidence',
                'importing',
                'completed',
                'failed'
            ])->default('pending');
            $table->string('status_message')->nullable();
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Results
            $table->json('metadata')->nullable(); // Input metadata
            $table->json('result')->nullable(); // Processing result
            $table->json('error')->nullable(); // Error details if failed
            
            $table->timestamps();

            // Indexes (shortened names to avoid MySQL 64 char limit)
            $table->index(['organization_id', 'user_id', 'created_at'], 'dpj_org_user_created_idx');
            $table->index(['status', 'created_at'], 'dpj_status_created_idx');
            $table->index('user_id', 'dpj_user_id_idx');
            
            // Foreign keys
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_processing_jobs');
    }
};

