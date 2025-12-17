# Sprint 6 Progress - Leave Management & Payroll

## Overview
Sprint 6 focuses on implementing Leave Management (Leave Types, Leave Requests) and Payroll System (Payroll Runs, Payroll Items) for team management.

## Completed Features

### ✅ 1. Migrations Completed
- **leave_types** - Full schema with organization_id, name, description, maximum_days_per_year, can_carry_forward, etc.
- **leave_requests** - Full schema with team_member_id, leave_type_id, dates, status, approval tracking
- **payroll_runs** - Full schema with pay_period, dates, status, total_amount
- **payroll_items** - Full schema with team_member_id, salary, allowances, deductions, net_pay
- **activity_logs** - Full schema with user_id, action_type, model_type, description, changes

### ✅ 2. Models Created
- **LeaveType** - With relationships to LeaveRequests
- **LeaveRequest** - With relationships to TeamMember, LeaveType, User (approvedBy)
- **PayrollRun** - With relationships to User (createdBy), PayrollItems
- **PayrollItem** - With relationships to PayrollRun, TeamMember
- **ActivityLog** - With static log() method for easy logging

### ✅ 3. Controllers Created
- **LeaveTypeController** - Full CRUD operations
- **LeaveRequestController** - Create, Show, Approve, Reject
- **PayrollRunController** - Create, Show, Process payroll runs
- **PayrollItemController** - Show payroll item details
- **ActivityLogController** - View activity logs with filters

### ✅ 4. Routes Registered
- Leave Types: `/leave/types` (CRUD)
- Leave Requests: `/leave/requests` (Create, Show, Approve, Reject)
- Payroll Runs: `/payroll/runs` (Create, Show, Process)
- Payroll Items: `/payroll/items/{id}` (Show)
- Activity Logs: `/activity-logs` (Index)

## Implementation Details

### Leave Types Management
- Create, read, update, delete leave types
- Track maximum days per year
- Support carry forward
- Prevent deletion if leave type has requests

### Leave Requests
- Submit leave requests with date range
- Automatic calculation of business days (excludes weekends)
- Status workflow: pending → approved/rejected
- Approval tracking with comments
- Filter by status, team member, leave type

### Payroll System
- Create payroll runs for a pay period
- Select team members for payroll
- Automatic calculation of gross pay, deductions, net pay
- Process payroll runs (draft → processing → completed)
- Track total payroll amount

### Activity Logs
- Track user activities
- Filter by user, action type, date range
- Store changes (old/new values)
- Track IP address and user agent

## Frontend Pages Needed

The following frontend pages need to be created:
- `/leave/types` - Leave types listing (Index.jsx)
- `/leave/types/create` - Create leave type (Create.jsx)
- `/leave/types/{id}/edit` - Edit leave type (Edit.jsx)
- `/leave/requests` - Leave requests listing (Index.jsx)
- `/leave/requests/create` - Submit leave request (Create.jsx)
- `/leave/requests/{id}` - View leave request (Show.jsx)
- `/payroll/runs` - Payroll runs listing (Index.jsx)
- `/payroll/runs/create` - Create payroll run (Create.jsx)
- `/payroll/runs/{id}` - View payroll run (Show.jsx)
- `/payroll/items/{id}` - View payroll item (Show.jsx)
- `/activity-logs` - Activity logs listing (Index.jsx)

## Integration Points

- ✅ Leave Requests linked to Team Members
- ✅ Leave Requests linked to Leave Types
- ✅ Payroll Items linked to Team Members and Payroll Runs
- ✅ Activity Logs track all important actions
- ✅ TeamMember model has leaveRequests relationship

## Next Steps

1. Create frontend pages for Leave Management
2. Create frontend pages for Payroll Management
3. Create Activity Logs listing page
4. Update navigation links (already has Leave and Payroll in People section)
5. Implement leave balance tracking (calculate from leave requests)
6. Add activity logging to key controllers

## Technical Notes

- Leave days calculation excludes weekends
- Payroll calculation supports allowances and deductions (JSON arrays)
- Activity logs can be logged using `ActivityLog::log()`
- All models use UUID primary keys
- All models respect organization scoping

## Status

**Backend**: ✅ Complete
**Frontend**: ⏳ Pending (pages need to be created)
**Routes**: ✅ Registered
**Models**: ✅ Complete
**Controllers**: ✅ Complete

