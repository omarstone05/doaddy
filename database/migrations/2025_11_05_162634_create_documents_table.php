<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // e.g., "contract", "policy", "report"
            $table->string('type')->nullable(); // e.g., "pdf", "doc", "excel"
            $table->enum('status', ['draft', 'active', 'archived'])->default('active');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable(); // in bytes
            $table->string('mime_type')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            // Foreign key for created_by_id will be added after users table exists
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'category']);
            });
            
            // Add foreign key after users table exists
            if (Schema::hasTable('users')) {
                Schema::table('documents', function (Blueprint $table) {
                    $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documents' AND COLUMN_NAME = 'created_by_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                    if (empty($foreignKeys)) {
                        $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
