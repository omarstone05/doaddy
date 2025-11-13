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
        if (!Schema::hasTable('document_versions')) {
            Schema::create('document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->integer('version_number');
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('changes')->nullable(); // Description of changes
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();
            
            // Foreign keys will be added after referenced tables exist
            $table->index(['document_id', 'version_number']);
            });
        }

        if (Schema::hasTable('document_versions') && Schema::hasTable('documents')) {
            Schema::table('document_versions', function (Blueprint $table) {
                $table->foreign('document_id')
                    ->references('id')
                    ->on('documents')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('document_versions') && Schema::hasTable('users')) {
            Schema::table('document_versions', function (Blueprint $table) {
                $table->foreign('created_by_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
