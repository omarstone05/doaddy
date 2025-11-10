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
        if (!Schema::hasTable('budget_lines')) {
            Schema::create('budget_lines', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id');
                $table->string('name');
                $table->string('category')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('period')->default('monthly'); // monthly, quarterly, yearly
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
                $table->index(['organization_id', 'category']);
            });
        } else {
            // Add missing columns if table exists but columns are missing
            Schema::table('budget_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('budget_lines', 'organization_id')) {
                    $table->uuid('organization_id')->after('id');
                    $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
                }
                if (!Schema::hasColumn('budget_lines', 'name')) {
                    $table->string('name')->after('organization_id');
                }
                if (!Schema::hasColumn('budget_lines', 'category')) {
                    $table->string('category')->nullable()->after('name');
                }
                if (!Schema::hasColumn('budget_lines', 'amount')) {
                    $table->decimal('amount', 12, 2)->after('category');
                }
                if (!Schema::hasColumn('budget_lines', 'period')) {
                    $table->string('period')->default('monthly')->after('amount');
                }
                if (!Schema::hasColumn('budget_lines', 'start_date')) {
                    $table->date('start_date')->nullable()->after('period');
                }
                if (!Schema::hasColumn('budget_lines', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('budget_lines', 'notes')) {
                    $table->text('notes')->nullable()->after('end_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
