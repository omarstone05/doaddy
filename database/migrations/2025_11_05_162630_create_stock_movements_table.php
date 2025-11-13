<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        }

        if (Schema::hasTable('stock_movements') && Schema::hasTable('goods_and_services')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreign('goods_service_id')
                    ->references('id')
                    ->on('goods_and_services')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('stock_movements') && Schema::hasTable('users')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreign('created_by_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
