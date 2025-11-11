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
        if (!Schema::hasTable('key_result_check_ins')) {
            Schema::create('key_result_check_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('key_result_id');
            $table->decimal('current_value', 15, 2);
            $table->integer('progress_percentage');
            $table->text('notes')->nullable();
            $table->enum('confidence', ['low', 'medium', 'high'])->default('medium');
            $table->uuid('checked_in_by_id')->nullable();
            $table->timestamps();
            
            // Foreign keys will be added after referenced tables exist
            $table->index(['key_result_id', 'created_at']);
            });
            
            // Add foreign keys after referenced tables exist
            foreach (['key_results', 'users'] as $refTable) {
                if (Schema::hasTable($refTable)) {
                    $column = match($refTable) {
                        'key_results' => 'key_result_id',
                        'users' => 'checked_in_by_id',
                    };
                    Schema::table('key_result_check_ins', function (Blueprint $table) use ($refTable, $column) {
                        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'key_result_check_ins' AND COLUMN_NAME = '{$column}' AND REFERENCED_TABLE_NAME IS NOT NULL");
                        if (empty($foreignKeys)) {
                            $onDelete = $column === 'checked_in_by_id' ? 'set null' : 'cascade';
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
        Schema::dropIfExists('key_result_check_ins');
    }
};
