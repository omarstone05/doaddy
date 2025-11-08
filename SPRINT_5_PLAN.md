# Sprint 5 Plan - Team Management & Reports

## Overview
Sprint 5 focuses on building out team management capabilities (Departments and Team Members) and basic reporting/analytics features to provide insights into business operations.

## Goals
- Complete team management interface
- Department management
- Team member CRUD operations
- Basic reports and analytics
- Enhanced dashboard with charts

## Features to Implement

### 1. Departments Management ✅ Priority
**Status**: Migration exists, needs implementation

**Tasks**:
- [ ] Departments Index page
- [ ] Create Department form
- [ ] Edit Department form
- [ ] View Department details
- [ ] Assign manager to department
- [ ] View team members in department
- [ ] Activate/deactivate departments

**Fields**:
- Name, Description
- Manager (Team Member)
- Active status

### 2. Team Members Management ✅ Priority
**Status**: Model exists, partially implemented (auto-created in POS)

**Tasks**:
- [ ] Team Members Index page
- [ ] Create Team Member form
- [ ] Edit Team Member form
- [ ] View Team Member details
- [ ] Assign to departments
- [ ] Link to user account (optional)
- [ ] Employment details (salary, job title, etc.)

**Fields**:
- Personal: First Name, Last Name, Email, Phone
- Employment: Employee Number, Hire Date, Job Title, Salary, Employment Type
- Address, Emergency Contact
- Active status

### 3. Basic Reports ✅ Priority
**Status**: Data exists, needs reporting interface

**Tasks**:
- [ ] Sales Report - Revenue by period, by product, by customer
- [ ] Revenue Report - Income breakdown
- [ ] Expense Report - Expense breakdown by category
- [ ] Profit & Loss Report
- [ ] Stock Report - Stock levels, low stock items
- [ ] Date range filters
- [ ] Export capabilities (CSV/PDF)

### 4. Enhanced Dashboard Analytics ✅ Medium Priority
**Status**: Basic dashboard exists, needs charts

**Tasks**:
- [ ] Revenue chart (line/bar chart)
- [ ] Expense chart
- [ ] Sales trend chart
- [ ] Top products chart
- [ ] Top customers chart
- [ ] Period comparison (this month vs last month)

### 5. Activity Logs ✅ Low Priority
**Status**: Migration exists, needs implementation

**Tasks**:
- [ ] Track user activities
- [ ] View activity logs
- [ ] Filter by user, date, action type

## Technical Implementation

### Models to Create/Enhance
- `Department` - Full model with relationships
- `TeamMember` - Enhance existing model
- `ActivityLog` - New model for tracking

### Controllers to Create
- `DepartmentController` - Department CRUD
- `TeamMemberController` - Team member CRUD (enhance existing)
- `ReportController` - Reports generation
- `ActivityLogController` - Activity logs viewing

### Frontend Pages to Create
- `/departments` - Departments listing
- `/departments/create` - Create department
- `/departments/{id}` - View department
- `/departments/{id}/edit` - Edit department
- `/team` - Team members listing
- `/team/create` - Create team member
- `/team/{id}` - View team member
- `/team/{id}/edit` - Edit team member
- `/reports` - Reports dashboard
- `/reports/sales` - Sales report
- `/reports/revenue` - Revenue report
- `/reports/expenses` - Expense report
- `/reports/profit-loss` - P&L report

### Routes to Add
```php
// Departments
Route::resource('departments', DepartmentController::class);

// Team Members
Route::resource('team', TeamMemberController::class)->names([
    'index' => 'team.index',
    'create' => 'team.create',
    'store' => 'team.store',
    'show' => 'team.show',
    'edit' => 'team.edit',
    'update' => 'team.update',
    'destroy' => 'team.destroy',
]);

// Reports
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
Route::get('/reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
```

## Integration Points
- Departments linked to Register Sessions
- Team Members linked to Sales (cashier)
- Team Members linked to Departments
- Reports pull from existing data (Sales, Money Movements, etc.)

## Success Criteria
- ✅ Full CRUD for departments
- ✅ Full CRUD for team members
- ✅ Department assignment working
- ✅ Basic reports functional
- ✅ Charts displaying on dashboard
- ✅ Date range filtering working

## Estimated Effort
- Departments: 3-4 hours
- Team Members: 4-5 hours
- Reports: 4-6 hours
- Dashboard Analytics: 3-4 hours
- **Total**: ~15-20 hours

## Next Sprint Preview
Sprint 6 could focus on:
- Leave Management
- Payroll
- Advanced Reports
- Settings & Configuration

