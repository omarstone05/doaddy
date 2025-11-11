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
        if (!Schema::hasTable('payroll_items')) {
            Schema::create('payroll_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_run_id');
            $table->uuid('team_member_id');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->json('allowances')->nullable(); // [{name: "Transport", amount: 500}, ...]
            $table->json('deductions')->nullable(); // [{name: "Tax", amount: 300}, ...]
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque'])->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Foreign keys will be added after referenced tables exist
            $table->index(['payroll_run_id', 'team_member_id']);
            });
            
            // Add foreign keys after referenced tables exist
            foreach (['payroll_runs', 'team_members'] as $refTable) {
                if (Schema::hasTable($refTable)) {
                    $column = match($refTable) {
                        'payroll_runs' => 'payroll_run_id',
                        'team_members' => 'team_member_id',
                    };
                    Schema::table('payroll_items', function (Blueprint $table) use ($refTable, $column) {
                        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payroll_items' AND COLUMN_NAME = '{$column}' AND REFERENCED_TABLE_NAME IS NOT NULL");
                        if (empty($foreignKeys)) {
                            $table->foreign($column)->references('id')->on($refTable)->onDelete('cascade');
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
        Schema::dropIfExists('payroll_items');
    }
};
