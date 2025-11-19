<?php

namespace App\Modules\ZambianHR\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZambianLeaveTypesSeeder extends Seeder
{
    /**
     * Seed Zambian-compliant leave types
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()->id ?? null;
        
        if (!$organizationId) {
            $this->command->warn('No organization found. Please create an organization first.');
            return;
        }

        $leaveTypes = [
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Annual Leave',
                'code' => 'ANNUAL',
                'description' => 'Annual leave entitlement - 2 days per month of service (24 days per year)',
                'color' => '#10B981', // green
                'annual_days' => 24,
                'max_carryover_days' => 0,
                'max_consecutive_days' => null,
                'min_notice_days' => 7,
                'accrual_method' => 'monthly', // 2 days per month
                'accrues_while_on_leave' => true,
                'requires_documentation' => false,
                'documentation_after_days' => null,
                'gender_specific' => null,
                'is_paid' => true,
                'payment_percentage' => 100,
                'is_active' => true,
                'monthly_recurring' => false,
                'max_per_month' => null,
                'min_notice_hours' => null,
                'eligibility_after_months' => 0,
                'requires_registered_dependent' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Sick Leave',
                'code' => 'SICK',
                'description' => 'First 3 months full pay, next 3 months half pay. Medical certificate required.',
                'color' => '#EF4444', // red
                'annual_days' => 180, // 6 months total
                'max_carryover_days' => 0,
                'max_consecutive_days' => 180,
                'min_notice_days' => 0, // can be immediate
                'accrual_method' => 'none',
                'accrues_while_on_leave' => false,
                'requires_documentation' => true,
                'documentation_after_days' => 1, // medical cert from day 1
                'gender_specific' => null,
                'is_paid' => true,
                'payment_percentage' => 100, // first 3 months, then 50% (handled in payroll)
                'is_active' => true,
                'monthly_recurring' => false,
                'max_per_month' => null,
                'min_notice_hours' => null,
                'eligibility_after_months' => 0,
                'requires_registered_dependent' => false,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Maternity Leave',
                'code' => 'MATERNITY',
                'description' => '90 days maternity leave with full pay (Zambian law)',
                'color' => '#EC4899', // pink
                'annual_days' => 90,
                'max_carryover_days' => 0,
                'max_consecutive_days' => 90,
                'min_notice_days' => 14,
                'accrual_method' => 'none',
                'accrues_while_on_leave' => true,
                'requires_documentation' => true,
                'documentation_after_days' => 0,
                'gender_specific' => 'female',
                'is_paid' => true,
                'payment_percentage' => 100,
                'is_active' => true,
                'monthly_recurring' => false,
                'max_per_month' => null,
                'min_notice_hours' => null,
                'eligibility_after_months' => 0,
                'requires_registered_dependent' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Paternity Leave',
                'code' => 'PATERNITY',
                'description' => '14 days paternity leave with full pay (Zambian law)',
                'color' => '#3B82F6', // blue
                'annual_days' => 14,
                'max_carryover_days' => 0,
                'max_consecutive_days' => 14,
                'min_notice_days' => 7,
                'accrual_method' => 'none',
                'accrues_while_on_leave' => true,
                'requires_documentation' => true,
                'documentation_after_days' => 0,
                'gender_specific' => 'male',
                'is_paid' => true,
                'payment_percentage' => 100,
                'is_active' => true,
                'monthly_recurring' => false,
                'max_per_month' => null,
                'min_notice_hours' => null,
                'eligibility_after_months' => 0,
                'requires_registered_dependent' => false,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => "Mother's Day Leave",
                'code' => 'MOTHERS_DAY',
                'description' => '1 day per month for female employees without medical certificate. Requires 1 day notice.',
                'color' => '#F59E0B', // amber
                'annual_days' => 12, // 1 per month × 12 months
                'max_carryover_days' => 0,
                'max_consecutive_days' => 1,
                'min_notice_days' => 1,
                'accrual_method' => 'monthly',
                'accrues_while_on_leave' => false,
                'requires_documentation' => false, // NO medical cert required
                'documentation_after_days' => null,
                'gender_specific' => 'female',
                'is_paid' => true,
                'payment_percentage' => 100,
                'is_active' => true,
                'monthly_recurring' => true, // 🔥 ZAMBIAN SPECIFIC
                'max_per_month' => 1, // Only 1 day per month
                'min_notice_hours' => 24, // 1 day = 24 hours notice
                'eligibility_after_months' => 0,
                'requires_registered_dependent' => false,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Family Responsibility Leave',
                'code' => 'FAMILY_RESP',
                'description' => '7 days per year to care for sick spouse, child, or dependent. Medical certificate required.',
                'color' => '#8B5CF6', // purple
                'annual_days' => 7,
                'max_carryover_days' => 0,
                'max_consecutive_days' => 7,
                'min_notice_days' => 1,
                'accrual_method' => 'annual',
                'accrues_while_on_leave' => false,
                'requires_documentation' => true, // Medical cert required
                'documentation_after_days' => 1,
                'gender_specific' => null,
                'is_paid' => true,
                'payment_percentage' => 100,
                'is_active' => true,
                'monthly_recurring' => false,
                'max_per_month' => null,
                'min_notice_hours' => null,
                'eligibility_after_months' => 6, // 🔥 Only after 6 months service
                'requires_registered_dependent' => true, // 🔥 Must have registered dependent
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Compassionate Leave (Bereavement)',
                'code' => 'COMPASSIONATE',
                'description' => '12 days per year for death of spouse, parent, child, or registered dependent',
                'color' => '#6B7280', // gray
                'annual_days' => 12,
                'max_carryover_days' => 0,
                'max_consecutive_days' => 12,
                'min_notice_days' => 0, // Can be immediate
                'accrual_method' => 'annual',
                'accrues_while_on_leave' => false,
                'requires_documentation' => true, // Death certificate
                'documentation_after_days' => 3,
                'gender_specific' => null,
                'is_paid' => true,
                'payment_percentage' => 100,
                'is_active' => true,
                'monthly_recurring' => false,
                'max_per_month' => null,
                'min_notice_hours' => null,
                'eligibility_after_months' => 0,
                'requires_registered_dependent' => true, // Must be registered
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Study Leave',
                'code' => 'STUDY',
                'description' => 'Leave for examinations or approved study programs',
                'color' => '#14B8A6', // teal
                'annual_days' => 0, // Discretionary
                'max_carryover_days' => 0,
                'max_consecutive_days' => null,
                'min_notice_days' => 14,
                'accrual_method' => 'none',
                'accrues_while_on_leave' => false,
                'requires_documentation' => true,
                'documentation_after_days' => 0,
                'gender_specific' => null,
                'is_paid' => true,
                'payment_percentage' => 100,
                'is_active' => true,
                'monthly_recurring' => false,
                'max_per_month' => null,
                'min_notice_hours' => null,
                'eligibility_after_months' => 12,
                'requires_registered_dependent' => false,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Unpaid Leave',
                'code' => 'UNPAID',
                'description' => 'Leave without pay (discretionary)',
                'color' => '#9CA3AF', // gray-400
                'annual_days' => 0,
                'max_carryover_days' => 0,
                'max_consecutive_days' => null,
                'min_notice_days' => 14,
                'accrual_method' => 'none',
                'accrues_while_on_leave' => false,
                'requires_documentation' => false,
                'documentation_after_days' => null,
                'gender_specific' => null,
                'is_paid' => false,
                'payment_percentage' => 0,
                'is_active' => true,
                'monthly_recurring' => false,
                'max_per_month' => null,
                'min_notice_hours' => null,
                'eligibility_after_months' => 0,
                'requires_registered_dependent' => false,
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('hr_leave_types')->insert($leaveTypes);

        $this->command->info('✅ Zambian leave types seeded successfully!');
        $this->command->info('   - Annual Leave (24 days/year)');
        $this->command->info('   - Sick Leave (6 months: 3 full, 3 half)');
        $this->command->info('   - Maternity Leave (90 days)');
        $this->command->info('   - Paternity Leave (14 days)');
        $this->command->info('   - Mother\'s Day Leave (1 day/month) 🇿🇲');
        $this->command->info('   - Family Responsibility Leave (7 days) 🇿🇲');
        $this->command->info('   - Compassionate Leave (12 days)');
        $this->command->info('   - Study Leave');
        $this->command->info('   - Unpaid Leave');
    }
}

