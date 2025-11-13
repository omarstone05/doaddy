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
        if (!Schema::hasTable('commission_earnings')) {
            Schema::create('commission_earnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('team_member_id');
            $table->uuid('sale_id');
            $table->uuid('commission_rule_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('sale_amount', 12, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            // Foreign keys for team_member_id, sale_id, and commission_rule_id will be added after those tables exist
            $table->index(['organization_id', 'team_member_id', 'status']);
            $table->index(['sale_id']);
            });
        }

        if (Schema::hasTable('commission_earnings') && Schema::hasTable('team_members')) {
            Schema::table('commission_earnings', function (Blueprint $table) {
                $table->foreign('team_member_id')
                    ->references('id')
                    ->on('team_members')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('commission_earnings') && Schema::hasTable('sales')) {
            Schema::table('commission_earnings', function (Blueprint $table) {
                $table->foreign('sale_id')
                    ->references('id')
                    ->on('sales')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('commission_earnings') && Schema::hasTable('commission_rules')) {
            Schema::table('commission_earnings', function (Blueprint $table) {
                $table->foreign('commission_rule_id')
                    ->references('id')
                    ->on('commission_rules')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_earnings');
    }
};
