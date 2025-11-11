<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('goods_service_id');
            $table->enum('movement_type', ['in', 'out', 'adjustment'])->default('out');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            // Foreign keys for goods_service_id and created_by_id will be added after those tables exist
            $table->index(['organization_id', 'goods_service_id']);
            $table->index('reference_number');
            });
            
            // Add foreign keys after referenced tables exist
            foreach (['goods_and_services', 'users'] as $refTable) {
                if (Schema::hasTable($refTable)) {
                    $column = match($refTable) {
                        'goods_and_services' => 'goods_service_id',
                        'users' => 'created_by_id',
                    };
                    Schema::table('stock_movements', function (Blueprint $table) use ($refTable, $column) {
                        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_movements' AND COLUMN_NAME = '{$column}' AND REFERENCED_TABLE_NAME IS NOT NULL");
                        if (empty($foreignKeys)) {
                            $onDelete = $column === 'created_by_id' ? 'set null' : 'cascade';
                            $table->foreign($column)->references('id')->on($refTable)->onDelete($onDelete);
                        }
                    });
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
