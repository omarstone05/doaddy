<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employee Beneficiaries
        Schema::create('hr_employee_beneficiaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('organization_id');
            
            // Beneficiary Details
            $table->string('beneficiary_name');
            $table->string('relationship'); // spouse, child, parent, sibling
            $table->date('date_of_birth')->nullable();
            $table->string('nrc_number')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            
            // Percentage
            $table->decimal('percentage', 5, 2)->default(100); // % of benefits
            $table->boolean('is_primary')->default(false);
            $table->integer('priority_order')->default(1);
            
            // Funeral Assistance
            $table->boolean('eligible_for_funeral_grant')->default(true);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Verification
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['employee_id', 'is_active']);
            
            // Only add foreign key if hr_employees table exists
            if (Schema::hasTable('hr_employees')) {
                $table->foreign('employee_id')
                      ->references('id')
                      ->on('hr_employees')
                      ->onDelete('cascade');
            }
                  
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Funeral Grants
        Schema::create('hr_funeral_grants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->nullable();
            $table->uuid('organization_id');
            
            // Death Details
            $table->string('deceased_person'); // employee, spouse, child, parent
            $table->string('deceased_name');
            $table->string('relationship_to_employee');
            $table->date('date_of_death');
            $table->string('death_certificate_file')->nullable();
            
            // Grant Details
            $table->decimal('grant_amount', 15, 2);
            $table->string('currency', 3)->default('ZMW');
            $table->text('calculation_basis')->nullable();
            
            // Payment
            $table->string('payment_method')->nullable();
            $table->uuid('paid_to_beneficiary_id')->nullable();
            $table->string('paid_to_name')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Documentation
            $table->json('supporting_documents')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index('employee_id');
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Gratuity Calculations
        Schema::create('hr_gratuity_calculations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('organization_id');
            
            // Calculation Period
            $table->date('calculation_date');
            $table->date('employment_start_date');
            $table->date('employment_end_date');
            $table->decimal('years_of_service', 8, 2);
            $table->integer('months_of_service');
            
            // Amounts
            $table->decimal('base_salary_used', 15, 2);
            $table->decimal('gratuity_rate', 5, 4)->default(0.25); // 25%
            $table->decimal('total_gratuity_amount', 15, 2);
            $table->decimal('prorated_amount', 15, 2)->nullable();
            
            // Payment
            $table->enum('status', ['calculated', 'approved', 'paid', 'cancelled'])->default('calculated');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            
            // Calculation Details
            $table->text('calculation_formula')->nullable();
            $table->json('calculation_breakdown')->nullable();
            
            // Deductions (if any)
            $table->decimal('deductions_amount', 15, 2)->default(0);
            $table->text('deductions_reason')->nullable();
            $table->decimal('net_gratuity_amount', 15, 2);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['employee_id', 'status']);
            $table->index('organization_id');
            
            // Only add foreign key if hr_employees table exists
            if (Schema::hasTable('hr_employees')) {
                $table->foreign('employee_id')
                      ->references('id')
                      ->on('hr_employees')
                      ->onDelete('cascade');
            }
                  
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Conflict of Interest Declarations
        Schema::create('hr_conflict_of_interest_declarations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('organization_id');
            
            // Declaration Type
            $table->enum('declaration_type', [
                'outside_employment', 'board_membership', 'business_interest', 
                'family_business', 'shareholding', 'consultancy', 'other'
            ]);
            
            // Details
            $table->string('organization_name');
            $table->string('position_held')->nullable();
            $table->text('nature_of_interest');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_ongoing')->default(true);
            
            // Financial Interest
            $table->decimal('monetary_value', 15, 2)->nullable();
            $table->decimal('ownership_percentage', 5, 2)->nullable();
            
            // Approval
            $table->enum('status', ['declared', 'under_review', 'approved', 'rejected', 'expired'])->default('declared');
            $table->date('declared_date');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('approval_decision')->nullable();
            $table->text('conditions_of_approval')->nullable();
            
            // Renewal
            $table->boolean('requires_annual_renewal')->default(true);
            $table->date('last_renewed_date')->nullable();
            $table->date('next_renewal_due')->nullable();
            
            // Documentation
            $table->json('supporting_documents')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['employee_id', 'status']);
            $table->index(['organization_id', 'status']);
            
            // Only add foreign key if hr_employees table exists
            if (Schema::hasTable('hr_employees')) {
                $table->foreign('employee_id')
                      ->references('id')
                      ->on('hr_employees')
                      ->onDelete('cascade');
            }
                  
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Grievances
        Schema::create('hr_grievances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('organization_id');
            
            // Grievance Details
            $table->string('grievance_number')->unique();
            $table->string('subject');
            $table->text('description');
            $table->enum('grievance_category', [
                'harassment', 'discrimination', 'working_conditions', 'salary',
                'benefits', 'management_action', 'safety', 'bullying', 'other'
            ]);
            
            // Parties Involved
            $table->uuid('filed_against_employee_id')->nullable();
            $table->uuid('filed_against_manager_id')->nullable();
            $table->json('witnesses')->nullable();
            
            // Dates
            $table->date('incident_date')->nullable();
            $table->date('filed_date');
            
            // Status
            $table->enum('status', [
                'submitted', 'under_investigation', 'pending_resolution',
                'resolved', 'closed', 'escalated', 'withdrawn'
            ])->default('submitted');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Investigation
            $table->uuid('assigned_to')->nullable();
            $table->date('investigation_start_date')->nullable();
            $table->text('investigation_notes')->nullable();
            $table->text('investigation_findings')->nullable();
            
            // Resolution
            $table->date('resolution_date')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->text('resolution_action_taken')->nullable();
            $table->enum('outcome', ['upheld', 'partially_upheld', 'not_upheld', 'withdrawn'])->nullable();
            
            // Appeal
            $table->boolean('appeal_filed')->default(false);
            $table->date('appeal_date')->nullable();
            $table->text('appeal_outcome')->nullable();
            
            // Confidentiality
            $table->boolean('is_confidential')->default(true);
            
            // Documentation
            $table->json('supporting_documents')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['employee_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index('grievance_number');
            
            // Only add foreign key if hr_employees table exists
            if (Schema::hasTable('hr_employees')) {
                $table->foreign('employee_id')
                      ->references('id')
                      ->on('hr_employees')
                      ->onDelete('cascade');
            }
                  
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Grievance Meetings
        Schema::create('hr_grievance_meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('grievance_id');
            
            // Meeting Details
            $table->enum('meeting_type', ['initial_hearing', 'investigation', 'resolution', 'appeal']);
            $table->date('meeting_date');
            $table->time('meeting_time')->nullable();
            $table->string('location')->nullable();
            
            // Attendees
            $table->json('attendees'); // array of employee IDs
            $table->uuid('chairperson_id')->nullable();
            
            // Minutes
            $table->text('minutes')->nullable();
            $table->text('decisions_made')->nullable();
            $table->json('action_items')->nullable();
            
            // Documentation
            $table->string('recording_file')->nullable();
            $table->string('meeting_notes_file')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('grievance_id');
            
            $table->foreign('grievance_id')
                  ->references('id')
                  ->on('hr_grievances')
                  ->onDelete('cascade');
        });

        // Contract Renewals
        Schema::create('hr_contract_renewals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('organization_id');
            
            // Current Contract
            $table->date('current_contract_start');
            $table->date('current_contract_end');
            
            // Renewal
            $table->enum('renewal_status', [
                'pending', 'offered', 'accepted', 'rejected', 'expired', 'withdrawn'
            ])->default('pending');
            $table->date('renewal_offered_date')->nullable();
            $table->date('renewal_deadline')->nullable();
            
            // New Contract Terms
            $table->date('new_contract_start')->nullable();
            $table->date('new_contract_end')->nullable();
            $table->string('new_contract_type')->nullable();
            $table->decimal('new_salary', 15, 2)->nullable();
            $table->string('new_job_title')->nullable();
            $table->text('changes_summary')->nullable();
            
            // Response
            $table->enum('employee_response', ['accepted', 'rejected', 'negotiating'])->nullable();
            $table->date('employee_response_date')->nullable();
            $table->text('employee_comments')->nullable();
            
            // Documentation
            $table->string('renewal_offer_file')->nullable();
            $table->string('new_contract_file')->nullable();
            
            // Initiated By
            $table->uuid('initiated_by');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['employee_id', 'renewal_status']);
            $table->index('organization_id');
            
            // Only add foreign key if hr_employees table exists
            if (Schema::hasTable('hr_employees')) {
                $table->foreign('employee_id')
                      ->references('id')
                      ->on('hr_employees')
                      ->onDelete('cascade');
            }
                  
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });

        // Enhanced Terminations
        Schema::create('hr_terminations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('organization_id');
            
            // Termination Details
            $table->enum('termination_type', [
                'resignation', 'notice', 'medical_discharge', 'redundancy',
                'summary_dismissal', 'retirement', 'death', 'contract_expiry', 
                'mutual_agreement', 'abandonment'
            ]);
            $table->date('termination_date');
            $table->date('last_working_day');
            
            // Notice
            $table->integer('notice_required_days')->default(30);
            $table->integer('notice_served_days')->default(0);
            $table->decimal('notice_payment_in_lieu', 15, 2)->default(0);
            
            // Reason
            $table->string('reason_category')->nullable();
            $table->text('reason_details')->nullable();
            
            // Severance/Benefits
            $table->string('severance_type')->nullable();
            $table->text('severance_calculation_basis')->nullable();
            $table->decimal('severance_amount', 15, 2)->default(0);
            
            // Medical Discharge (3 months per year of service)
            $table->decimal('medical_discharge_months_per_year', 5, 2)->default(3.00);
            $table->decimal('medical_discharge_total', 15, 2)->default(0);
            
            // Redundancy (2 months per year of service)
            $table->decimal('redundancy_months_per_year', 5, 2)->default(2.00);
            $table->decimal('redundancy_total', 15, 2)->default(0);
            
            // Gratuity (25% of basic pay per year)
            $table->decimal('gratuity_amount', 15, 2)->default(0);
            $table->boolean('gratuity_prorated')->default(false);
            
            // Leave Settlement
            $table->decimal('leave_days_outstanding', 8, 2)->default(0);
            $table->decimal('leave_payout_amount', 15, 2)->default(0);
            
            // Other Dues
            $table->decimal('other_amounts_due', 15, 2)->default(0);
            $table->text('other_amounts_description')->nullable();
            
            // Deductions/Recovery
            $table->decimal('amounts_to_recover', 15, 2)->default(0);
            $table->text('recovery_reason')->nullable();
            
            // Final Settlement
            $table->decimal('total_gross_amount', 15, 2);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_settlement_amount', 15, 2);
            
            // Payment
            $table->boolean('settlement_paid')->default(false);
            $table->date('settlement_paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            
            // Exit Process
            $table->boolean('exit_interview_completed')->default(false);
            $table->date('exit_interview_date')->nullable();
            $table->text('exit_interview_notes')->nullable();
            
            $table->boolean('clearance_form_completed')->default(false);
            $table->boolean('all_property_returned')->default(false);
            $table->json('property_return_checklist')->nullable();
            
            // Re-hire Eligibility
            $table->boolean('eligible_for_rehire')->default(true);
            $table->text('rehire_notes')->nullable();
            
            // Approval
            $table->uuid('initiated_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Documentation
            $table->string('termination_letter_file')->nullable();
            $table->string('settlement_letter_file')->nullable();
            $table->json('supporting_documents')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['employee_id']);
            $table->index(['organization_id', 'termination_type']);
            $table->index('termination_date');
            
            // Only add foreign key if hr_employees table exists
            if (Schema::hasTable('hr_employees')) {
                $table->foreign('employee_id')
                      ->references('id')
                      ->on('hr_employees')
                      ->onDelete('cascade');
            }
                  
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_terminations');
        Schema::dropIfExists('hr_contract_renewals');
        Schema::dropIfExists('hr_grievance_meetings');
        Schema::dropIfExists('hr_grievances');
        Schema::dropIfExists('hr_conflict_of_interest_declarations');
        Schema::dropIfExists('hr_gratuity_calculations');
        Schema::dropIfExists('hr_funeral_grants');
        Schema::dropIfExists('hr_employee_beneficiaries');
    }
};

