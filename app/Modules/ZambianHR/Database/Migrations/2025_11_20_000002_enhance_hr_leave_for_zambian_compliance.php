<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only enhance if hr_leave_types table exists
        if (!Schema::hasTable('hr_leave_types')) {
            return;
        }

        // Add Mother's Day Leave specific fields to leave types (only if columns don't exist)
        Schema::table('hr_leave_types', function (Blueprint $table) {
            // Check if columns exist before adding
            if (!Schema::hasColumn('hr_leave_types', 'monthly_recurring')) {
                $table->boolean('monthly_recurring')->default(false)->after('accrual_method');
            }
            if (!Schema::hasColumn('hr_leave_types', 'max_per_month')) {
                $table->decimal('max_per_month', 5, 2)->nullable()->after('monthly_recurring');
            }
            if (!Schema::hasColumn('hr_leave_types', 'min_notice_hours')) {
                $table->integer('min_notice_hours')->nullable()->after('min_notice_days');
            }
            if (!Schema::hasColumn('hr_leave_types', 'eligibility_after_months')) {
                $table->integer('eligibility_after_months')->default(0)->after('min_notice_hours');
            }
            if (!Schema::hasColumn('hr_leave_types', 'requires_registered_dependent')) {
                $table->boolean('requires_registered_dependent')->default(false)->after('eligibility_after_months');
            }
        });

        // Only enhance if hr_leave_requests table exists
        if (!Schema::hasTable('hr_leave_requests')) {
            return;
        }

        // Add dependent reference to leave requests (only if columns don't exist)
        Schema::table('hr_leave_requests', function (Blueprint $table) {
            // For Family Responsibility Leave - link to dependent
            if (!Schema::hasColumn('hr_leave_requests', 'dependent_id')) {
                $table->uuid('dependent_id')->nullable()->after('leave_type_id');
            }
            
            // Medical certificate for family responsibility
            if (!Schema::hasColumn('hr_leave_requests', 'medical_certificate_file')) {
                $table->string('medical_certificate_file')->nullable()->after('attachments');
            }
            if (!Schema::hasColumn('hr_leave_requests', 'relationship_to_patient')) {
                $table->string('relationship_to_patient')->nullable()->after('medical_certificate_file');
            }
            
            // Mother's Day specific
            if (!Schema::hasColumn('hr_leave_requests', 'is_mothers_day_leave')) {
                $table->boolean('is_mothers_day_leave')->default(false)->after('is_half_day');
            }
            
            // Index (only if column exists and index doesn't exist)
            if (Schema::hasColumn('hr_leave_requests', 'dependent_id')) {
                try {
                    $table->index('dependent_id');
                } catch (\Exception $e) {
                    // Index might already exist, ignore
                }
            }
            
            // Note: Foreign key will be added if hr_dependents table exists
            // $table->foreign('dependent_id')
            //       ->references('id')
            //       ->on('hr_dependents')
            //       ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_leave_requests')) {
            return;
        }

        Schema::table('hr_leave_requests', function (Blueprint $table) {
            $table->dropIndex(['dependent_id']);
            $table->dropColumn([
                'dependent_id',
                'medical_certificate_file',
                'relationship_to_patient',
                'is_mothers_day_leave'
            ]);
        });
        
        if (!Schema::hasTable('hr_leave_types')) {
            return;
        }

        Schema::table('hr_leave_types', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_recurring',
                'max_per_month',
                'min_notice_hours',
                'eligibility_after_months',
                'requires_registered_dependent'
            ]);
        });
    }
};

