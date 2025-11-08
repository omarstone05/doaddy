# Sprint 6 Plan - Leave Management & Payroll

## Overview
Sprint 6 focuses on implementing Leave Management (Leave Types, Leave Requests, Leave Balances) and Payroll System (Payroll Runs, Payroll Items) for team management.

## Goals
- Complete leave management system
- Leave request workflow (submit, approve, reject)
- Leave balance tracking
- Payroll run creation and management
- Payroll item calculation
- Activity logging

## Features to Implement

### 1. Leave Types Management ✅ Priority
**Status**: Migration exists but empty, needs schema and implementation

**Tasks**:
- [ ] Complete leave_types migration schema
- [ ] Create LeaveType model
- [ ] Leave Types CRUD (Index, Create, Edit, Delete)
- [ ] Default leave types (Annual, Sick, Casual, etc.)

**Fields**:
- Name (e.g., "Annual Leave", "Sick Leave")
- Description
- Maximum days per year
- Carry forward (boolean)
- Is active

### 2. Leave Requests ✅ Priority
**Status**: Migration exists but empty, needs schema and implementation

**Tasks**:
- [ ] Complete leave_requests migration schema
- [ ] Create LeaveRequest model
- [ ] Submit leave request form
- [ ] Approve/Reject leave requests
- [ ] View leave request history
- [ ] Calendar view for leave requests

**Fields**:
- Team Member ID
- Leave Type ID
- Start Date
- End Date
- Number of Days
- Reason
- Status (pending, approved, rejected, cancelled)
- Approved By
- Approved At
- Comments

### 3. Leave Balance Tracking ✅ Priority
**Status**: Needs implementation

**Tasks**:
- [ ] Track leave balances per team member per leave type
- [ ] Calculate available leave days
- [ ] Display leave balances on team member profile
- [ ] Leave balance history

**Implementation**:
- Calculate from leave requests
- Show available vs used vs pending
- Reset balances annually

### 4. Payroll Runs ✅ Priority
**Status**: Migration exists but empty, needs schema and implementation

**Tasks**:
- [ ] Complete payroll_runs migration schema
- [ ] Create PayrollRun model
- [ ] Create payroll run
- [ ] Process payroll for selected team members
- [ ] View payroll run history
- [ ] Generate payroll reports

**Fields**:
- Pay Period (month/year)
- Start Date
- End Date
- Status (draft, processing, completed, cancelled)
- Total Amount
- Created By
- Processed At

### 5. Payroll Items ✅ Priority
**Status**: Migration exists but empty, needs schema and implementation

**Tasks**:
- [ ] Complete payroll_items migration schema
- [ ] Create PayrollItem model
- [ ] Calculate salary, deductions, allowances
- [ ] Generate payroll items per team member
- [ ] View payroll item details

**Fields**:
- Payroll Run ID
- Team Member ID
- Basic Salary
- Allowances (JSON)
- Deductions (JSON)
- Gross Pay
- Net Pay
- Payment Method
- Payment Date

### 6. Activity Logs ✅ Medium Priority
**Status**: Migration exists but empty, needs schema and implementation

**Tasks**:
- [ ] Complete activity_logs migration schema
- [ ] Create ActivityLog model
- [ ] Log user activities
- [ ] View activity logs
- [ ] Filter by user, date, action type

**Fields**:
- User ID
- Action Type (create, update, delete, etc.)
- Model Type
- Model ID
- Description
- IP Address
- User Agent

## Technical Implementation

### Migrations to Complete
- `leave_types` - Add proper schema
- `leave_requests` - Add proper schema
- `payroll_runs` - Add proper schema
- `payroll_items` - Add proper schema
- `activity_logs` - Add proper schema

### Models to Create
- `LeaveType` - Leave type management
- `LeaveRequest` - Leave request tracking
- `PayrollRun` - Payroll run management
- `PayrollItem` - Payroll item details
- `ActivityLog` - Activity tracking

### Controllers to Create
- `LeaveTypeController` - Leave type CRUD
- `LeaveRequestController` - Leave request management
- `PayrollRunController` - Payroll run management
- `PayrollItemController` - Payroll item management
- `ActivityLogController` - Activity log viewing

### Frontend Pages to Create
- `/leave/types` - Leave types listing
- `/leave/types/create` - Create leave type
- `/leave/types/{id}/edit` - Edit leave type
- `/leave/requests` - Leave requests listing
- `/leave/requests/create` - Submit leave request
- `/leave/requests/{id}` - View leave request
- `/payroll/runs` - Payroll runs listing
- `/payroll/runs/create` - Create payroll run
- `/payroll/runs/{id}` - View payroll run
- `/payroll/items/{id}` - View payroll item
- `/activity-logs` - Activity logs listing

### Routes to Add
```php
// Leave Types
Route::resource('leave/types', LeaveTypeController::class)->names([
    'index' => 'leave.types.index',
    'create' => 'leave.types.create',
    'store' => 'leave.types.store',
    'edit' => 'leave.types.edit',
    'update' => 'leave.types.update',
    'destroy' => 'leave.types.destroy',
]);

// Leave Requests
Route::resource('leave/requests', LeaveRequestController::class)->names([
    'index' => 'leave.requests.index',
    'create' => 'leave.requests.create',
    'store' => 'leave.requests.store',
    'show' => 'leave.requests.show',
]);
Route::post('/leave/requests/{id}/approve', [LeaveRequestController::class, 'approve'])->name('leave.requests.approve');
Route::post('/leave/requests/{id}/reject', [LeaveRequestController::class, 'reject'])->name('leave.requests.reject');

// Payroll Runs
Route::resource('payroll/runs', PayrollRunController::class)->names([
    'index' => 'payroll.runs.index',
    'create' => 'payroll.runs.create',
    'store' => 'payroll.runs.store',
    'show' => 'payroll.runs.show',
]);
Route::post('/payroll/runs/{id}/process', [PayrollRunController::class, 'process'])->name('payroll.runs.process');

// Payroll Items
Route::get('/payroll/items/{id}', [PayrollItemController::class, 'show'])->name('payroll.items.show');

// Activity Logs
Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
```

## Integration Points
- Leave Requests linked to Team Members
- Leave Balances calculated from Leave Requests
- Payroll Items linked to Team Members and Payroll Runs
- Payroll Runs generate Money Movements
- Activity Logs track all important actions

## Success Criteria
- ✅ Full CRUD for leave types
- ✅ Submit and manage leave requests
- ✅ Approve/reject leave requests
- ✅ Track leave balances
- ✅ Create and process payroll runs
- ✅ Calculate payroll items
- ✅ View activity logs
- ✅ All routes properly registered

## Estimated Effort
- Leave Types: 2-3 hours
- Leave Requests: 4-5 hours
- Leave Balances: 2-3 hours
- Payroll Runs: 4-5 hours
- Payroll Items: 3-4 hours
- Activity Logs: 2-3 hours
- **Total**: ~17-23 hours

