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

        // Add Mother's Day Leave specific fields to leave types
        Schema::table('hr_leave_types', function (Blueprint $table) {
            // Monthly recurring leave (for Mother's Day)
            $table->boolean('monthly_recurring')->default(false)->after('accrual_method');
            $table->decimal('max_per_month', 5, 2)->nullable()->after('monthly_recurring');
            
            // Notice period in hours (for 1 day notice = 24 hours)
            $table->integer('min_notice_hours')->nullable()->after('min_notice_days');
            
            // Eligibility based on service
            $table->integer('eligibility_after_months')->default(0)->after('min_notice_hours');
            
            // Must register dependents
            $table->boolean('requires_registered_dependent')->default(false)->after('eligibility_after_months');
        });

        // Only enhance if hr_leave_requests table exists
        if (!Schema::hasTable('hr_leave_requests')) {
            return;
        }

        // Add dependent reference to leave requests
        Schema::table('hr_leave_requests', function (Blueprint $table) {
            // For Family Responsibility Leave - link to dependent
            $table->uuid('dependent_id')->nullable()->after('leave_type_id');
            
            // Medical certificate for family responsibility
            $table->string('medical_certificate_file')->nullable()->after('attachments');
            $table->string('relationship_to_patient')->nullable()->after('medical_certificate_file');
            
            // Mother's Day specific
            $table->boolean('is_mothers_day_leave')->default(false)->after('is_half_day');
            
            // Index
            $table->index('dependent_id');
            
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

