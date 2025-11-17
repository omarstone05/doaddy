<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->string('original_name');
            $table->string('file_name');
            $table->string('file_type')->default('document'); // receipt, invoice, csv, document, etc.
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('storage_driver')->default('local'); // local, google
            $table->string('storage_path'); // File path or Google Drive file ID
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamp('processed_at')->nullable();
            $table->json('processing_result')->nullable(); // OCR results, import results, etc.
            $table->timestamps();

            // Indexes
            $table->index(['organization_id', 'user_id', 'created_at'], 'uf_org_user_created_idx');
            $table->index(['file_type', 'created_at'], 'uf_type_created_idx');
            $table->index('user_id', 'uf_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};

