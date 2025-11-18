# Project Management Roles and Granular Task View

## Overview

This implementation adds project management-specific roles and a comprehensive granular task view that allows users to see detailed task information at a fine-grained level.

## Components Created

### 1. Project Management Roles (`database/seeders/ProjectManagementRolesSeeder.php`)

Four new system roles have been created specifically for project management:

#### **Project Manager** (Level 70)
- **Full access** to all project management features
- Can create, edit, and delete projects and tasks
- Can assign tasks and manage team members
- Can view granular task details
- Can manage budgets and time tracking
- **Permissions:**
  - `projects.*` (view, create, edit, delete, assign)
  - `tasks.*` (view, create, edit, delete, assign, view_granular)
  - `milestones.*` (view, create, edit, delete)
  - `team.*` (view, manage)
  - `budget.*` (view, manage)
  - `time.*` (view, manage)
  - `reports.view`

#### **Project Coordinator** (Level 55)
- Can coordinate projects and manage tasks
- Limited project creation and deletion permissions
- Can view granular task details
- **Permissions:**
  - `projects.view`, `projects.create`, `projects.edit`
  - `tasks.*` (view, create, edit, assign, view_granular)
  - `milestones.*` (view, create, edit)
  - `team.view`
  - `budget.view`
  - `time.view`, `time.manage`
  - `reports.view`

#### **Task Assignee** (Level 35)
- Can view and manage **assigned tasks** with granular detail
- Limited to own tasks and assigned projects
- **Permissions:**
  - `projects.view`
  - `tasks.view`, `tasks.edit` (own tasks only)
  - `tasks.view_granular`
  - `milestones.view`
  - `time.view`, `time.create` (own time entries)

#### **Project Viewer** (Level 25)
- **Read-only** access to projects and tasks
- Can view granular task details but cannot make changes
- **Permissions:**
  - `projects.view`
  - `tasks.view`, `tasks.view_granular`
  - `milestones.view`
  - `time.view`
  - `reports.view`

### 2. Granular Task Detail View (`resources/js/Pages/Projects/Tasks/Show.jsx`)

A comprehensive task detail page that displays:

#### **Main Content:**
- **Task Title** - Editable when user has edit permissions
- **Description** - Full task description with markdown support
- **Subtasks** - List of all subtasks with progress tracking
- **Time Entries** - Detailed time logging history
- **Project Link** - Quick navigation back to parent project

#### **Sidebar Information:**
- **Task Details:**
  - Status (Todo, In Progress, Review, Done, Blocked)
  - Priority (Low, Medium, High, Urgent)
  - Assigned To (User assignment)
  - Created By (Task creator)
  - Due Date
  - Start Date
  - Time Tracking (Estimated, Actual, Total Logged)
  - Tags

- **Statistics:**
  - Subtask progress bar
  - Time tracking summary
  - Completion metrics

#### **Features:**
- **Inline Editing** - Edit task details directly on the page (if user has permissions)
- **Permission-Based Access** - Only users with appropriate roles can view/edit/delete
- **Responsive Design** - Works on desktop and mobile devices
- **Real-time Updates** - Changes reflect immediately

### 3. Updated Components

#### **TaskCard Component** (`resources/js/Components/projects/TaskCard.jsx`)
- Made clickable to navigate to granular task view
- Added hover effects for better UX
- Links to `/projects/{projectId}/tasks/{taskId}`

#### **ProjectTaskController** (`app/Http/Controllers/ProjectTaskController.php`)
- Added `show()` method for granular task view
- Added permission checking methods:
  - `canEditTask()` - Checks if user can edit task
  - `canDeleteTask()` - Checks if user can delete task
- Permission logic:
  - Project Managers and Coordinators can edit any task
  - Task Assignees can edit their own assigned tasks
  - Only Project Managers can delete tasks
  - Creators can edit/delete their own unassigned tasks

### 4. Routes

New route added:
```php
Route::prefix('projects/{project}/tasks')->name('projects.tasks.')->group(function () {
    Route::get('/{task}', [ProjectTaskController::class, 'show'])->name('show');
});
```

## Permission System

### Access Control Logic

1. **Granular View Access:**
   - Users with `tasks.view_granular` permission can view any task
   - Users assigned to a task can view it
   - Project managers can view tasks in their projects
   - Task creators can view their own tasks

2. **Edit Permissions:**
   - Project Managers: Can edit any task
   - Project Coordinators: Can edit any task
   - Task Assignees: Can edit only their assigned tasks
   - Task Creators: Can edit their own tasks

3. **Delete Permissions:**
   - Only Project Managers can delete tasks
   - Task Creators can delete their own unassigned tasks

## Usage

### Assigning Roles

To assign a project management role to a user:

```php
$user->assignRoleInOrganization($organizationId, 'project_manager');
// or
$user->assignRoleInOrganization($organizationId, 'project_coordinator');
$user->assignRoleInOrganization($organizationId, 'task_assignee');
$user->assignRoleInOrganization($organizationId, 'project_viewer');
```

### Accessing Granular Task View

1. Navigate to a project
2. Click on any task card
3. You'll be taken to the granular task detail page at:
   `/projects/{projectId}/tasks/{taskId}`

### Seeding Roles

Run the seeder to create the roles:

```bash
php artisan db:seed --class=ProjectManagementRolesSeeder
```

## Benefits

1. **Role-Based Access Control** - Clear separation of permissions based on job function
2. **Granular Visibility** - Users can see detailed task information at a fine-grained level
3. **Better Task Management** - Comprehensive view of all task-related information in one place
4. **Permission Security** - Proper access control ensures users only see/edit what they should
5. **Scalable** - Easy to add more roles or permissions as needed

## Future Enhancements

Potential improvements:
- Task comments/activity feed
- File attachments
- Task dependencies visualization
- Time tracking directly from task view
- Task templates
- Bulk task operations
- Task notifications

