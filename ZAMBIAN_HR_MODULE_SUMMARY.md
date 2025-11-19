# 🇿🇲 Zambian HR Module - Complete Summary

## Overview

A comprehensive Zambian labor law compliant HR module that extends the base HR module with Zambian-specific features and compliance requirements.

---

## ✅ What Was Created

### 1. Module Structure
- **Location**: `app/Modules/ZambianHR/`
- **Module Config**: `module.json` with Zambian-specific metadata
- **Service Provider**: `ZambianHRServiceProvider.php`
- **Dependencies**: Requires base HR module to be enabled

### 2. Database Migrations (2 files)

#### Migration 1: Contract Compliance Tables
**File**: `2025_11_20_000001_create_zambian_hr_contract_compliance_tables.php`

Creates 8 essential tables:
1. `hr_employee_beneficiaries` - Beneficiary management for funeral grants
2. `hr_funeral_grants` - Funeral assistance tracking
3. `hr_gratuity_calculations` - 25% gratuity calculation system
4. `hr_conflict_of_interest_declarations` - Outside employment tracking
5. `hr_grievances` - Employee grievance management
6. `hr_grievance_meetings` - Grievance investigation meetings
7. `hr_contract_renewals` - Fixed-term contract management
8. `hr_terminations` - Enhanced termination with severance calculations

#### Migration 2: Leave Enhancements
**File**: `2025_11_20_000002_enhance_hr_leave_for_zambian_compliance.php`

Enhances `hr_leave_types` and `hr_leave_requests` tables with:
- `monthly_recurring` - For Mother's Day leave
- `max_per_month` - Limit monthly leave days
- `min_notice_hours` - Notice period in hours (24 hours for Mother's Day)
- `eligibility_after_months` - Service length requirements
- `requires_registered_dependent` - For Family Responsibility Leave
- `dependent_id` - Link to dependents for Family Responsibility Leave
- `medical_certificate_file` - Medical cert tracking
- `is_mothers_day_leave` - Flag for Mother's Day leave

### 3. Models (8 models)

1. **EmployeeBeneficiary** - Beneficiary records
2. **FuneralGrant** - Funeral grant applications and payments
3. **GratuityCalculation** - Gratuity calculations and payments
4. **Grievance** - Employee grievances
5. **GrievanceMeeting** - Grievance investigation meetings
6. **Termination** - Enhanced termination records with severance
7. **ContractRenewal** - Contract renewal workflow
8. **ConflictOfInterest** - Conflict of interest declarations

### 4. Controllers (7 controllers)

1. **ZambianHRDashboardController** - Dashboard with Zambian HR stats
2. **FuneralGrantController** - CRUD for funeral grants
3. **GratuityController** - Calculate and manage gratuity (25%)
4. **GrievanceController** - File and manage grievances
5. **TerminationController** - Process terminations with severance
6. **ContractRenewalController** - Manage contract renewals
7. **ConflictOfInterestController** - Track conflict of interest

### 5. Routes

**File**: `Routes/web.php`

All routes prefixed with `/zambian-hr`:
- `/zambian-hr/dashboard` - Main dashboard
- `/zambian-hr/funeral-grants` - Funeral grant management
- `/zambian-hr/gratuity` - Gratuity calculations
- `/zambian-hr/grievances` - Grievance management
- `/zambian-hr/terminations` - Termination processing
- `/zambian-hr/contract-renewals` - Contract renewals
- `/zambian-hr/conflict-of-interest` - Conflict declarations

### 6. Seeder

**File**: `Seeders/ZambianLeaveTypesSeeder.php`

Seeds 9 Zambian-compliant leave types:
1. Annual Leave (24 days/year)
2. Sick Leave (6 months: 3 full + 3 half pay)
3. Maternity Leave (90 days)
4. Paternity Leave (14 days)
5. **Mother's Day Leave** (1 day/month) 🇿🇲
6. **Family Responsibility Leave** (7 days) 🇿🇲
7. Compassionate Leave (12 days)
8. Study Leave
9. Unpaid Leave

### 7. Frontend

**File**: `Resources/js/Pages/Dashboard.jsx`

Basic dashboard showing:
- Pending funeral grants
- Pending gratuity calculations
- Active grievances
- Pending terminations
- Contracts expiring soon

---

## 🇿🇲 Zambian-Specific Features

### 1. Funeral Grants System
- Beneficiary registration
- Death benefit tracking
- Quick approval workflow
- Payment to beneficiaries
- Death certificate upload

### 2. Gratuity System (25%)
- Automatic calculation: 25% × (Base Salary × Years of Service)
- Pro-rata for partial years
- Deduction handling
- Payment tracking
- Applies to all terminations

### 3. Mother's Day Leave 🇿🇲
- **Unique to Zambian law**
- 1 day per month for female employees
- 12 days per year total
- No medical certificate required
- 24 hours notice
- Cannot be denied

### 4. Family Responsibility Leave 🇿🇲
- 7 days per year
- Eligibility: After 6 months service
- Requires registered dependent
- Medical certificate required
- For caring for sick spouse/child/dependent

### 5. Enhanced Termination System
- **Medical Discharge**: 3 months pay per year of service
- **Redundancy**: 2 months pay per year of service
- **Notice Termination**: 1 month payment in lieu
- **Gratuity**: 25% on all terminations
- Leave settlement
- Exit interview tracking
- Property return checklist

### 6. Grievance Management
- Grievance filing
- Investigation workflow
- Meeting tracking
- Resolution management
- Appeal process
- Confidentiality settings

### 7. Contract Renewals
- Fixed-term contract tracking
- Renewal workflow
- Expiry notifications
- Terms negotiation
- Employee response tracking

### 8. Conflict of Interest
- Outside employment declarations
- Board membership tracking
- Business interest tracking
- Annual renewal requirements
- Approval workflow

---

## 📊 Database Schema Summary

### Total Tables: 8 New Tables + 2 Enhanced

**New Tables:**
1. `hr_employee_beneficiaries`
2. `hr_funeral_grants`
3. `hr_gratuity_calculations`
4. `hr_conflict_of_interest_declarations`
5. `hr_grievances`
6. `hr_grievance_meetings`
7. `hr_contract_renewals`
8. `hr_terminations`

**Enhanced Tables:**
1. `hr_leave_types` (added 5 new columns)
2. `hr_leave_requests` (added 4 new columns)

---

## 🚀 Usage

### 1. Enable Module
```bash
# In Settings → Modules, enable "Zambian HR"
# Requires base HR module to be enabled first
```

### 2. Run Migrations
```bash
php artisan migrate --path=app/Modules/ZambianHR/Database/Migrations
```

### 3. Seed Leave Types
```bash
php artisan db:seed --class="App\Modules\ZambianHR\Seeders\ZambianLeaveTypesSeeder"
```

### 4. Access Module
Navigate to: `/zambian-hr/dashboard`

---

## 🔗 Integration

### With Base HR Module
- Extends HR employee records
- Uses HR leave system (enhanced)
- Links to HR employees table

### With Settings → People
- Commissions flow to payroll
- Team members link to employees

### Module Dependencies
- **Requires**: HR module (base)
- **Optional**: Can work standalone if HR tables exist

---

## 📋 Key Calculations

### Gratuity Formula
```
Gratuity = 25% × (Base Salary × Years of Service)
Pro-rata = 25% × (Base Salary × (Months % 12 / 12))
Total = Gratuity + Pro-rata - Deductions
```

### Medical Discharge Severance
```
Severance = 3 months × Years of Service × Base Salary
```

### Redundancy Severance
```
Severance = 2 months × Years of Service × Base Salary
```

---

## ✅ Compliance Checklist

- [x] Funeral assistance system
- [x] Gratuity calculations (25%)
- [x] Mother's Day leave (1 day/month)
- [x] Family responsibility leave (7 days)
- [x] Enhanced termination tracking
- [x] Grievance management
- [x] Contract renewal workflow
- [x] Conflict of interest declarations
- [x] Zambian leave types (9 types)
- [x] Service-based eligibility
- [x] Dependent registration requirements

---

## 🎯 Next Steps

1. **Frontend Pages**: Create full CRUD pages for all features
2. **Reports**: Generate Zambian compliance reports
3. **Notifications**: Email/SMS notifications for approvals
4. **PDF Generation**: Generate settlement letters, termination letters
5. **Integration**: Link with payroll for automatic deductions
6. **Workflows**: Approval workflows for all processes

---

## 📝 Notes

- Module is **disabled by default**
- Requires **HR module** to be enabled first
- All routes are protected with `auth` and `verified` middleware
- Uses same organization isolation as base HR module
- All calculations follow Zambian labor law

---

**Module Created**: November 20, 2025  
**Version**: 1.0.0  
**Status**: ✅ Complete (Backend), 🚧 Frontend pages pending

