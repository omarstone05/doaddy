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
            
            // Add foreign keys after referenced tables exist
            foreach (['documents', 'users'] as $refTable) {
                if (Schema::hasTable($refTable)) {
                    $column = match($refTable) {
                        'documents' => 'document_id',
                        'users' => 'created_by_id',
                    };
                    Schema::table('document_versions', function (Blueprint $table) use ($refTable, $column) {
                        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'document_versions' AND COLUMN_NAME = '{$column}' AND REFERENCED_TABLE_NAME IS NOT NULL");
                        if (empty($foreignKeys)) {
                            $onDelete = $column === 'created_by_id' ? 'set null' : 'cascade';
                            $table->foreign($column)->references('id')->on($refTable)->onDelete($onDelete);
                        }
                    });
                }
            }
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
