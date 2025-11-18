# Project Management Test Data Seeder

This document explains how to seed test data for the Project Management section to populate the charts and visualizations.

## Overview

The `ProjectManagementTestDataSeeder` creates comprehensive test data including:
- **15 Projects** with varied statuses, priorities, and progress levels
- **Tasks** for each project with different statuses (todo, in_progress, done)
- **Milestones** for each project
- **Time Entries** spread over the past 3 months
- **Budget Entries** with allocated and spent amounts
- **Project Members** assigned to projects

## Data Distribution

### Projects by Status:
- **Active**: 5 projects (various progress levels)
- **Planning**: 3 projects (0% progress)
- **On Hold**: 2 projects (low progress)
- **Completed**: 4 projects (100% progress)
- **Cancelled**: 1 project

### Projects by Priority:
- **Urgent**: 3 projects
- **High**: 4 projects
- **Medium**: 6 projects
- **Low**: 2 projects

### Tasks by Status:
- **Done**: ~40% of tasks
- **In Progress**: ~30% of tasks
- **Todo**: ~30% of tasks

## Running the Seeder

### Option 1: Run the seeder directly

```bash
php artisan db:seed --class=ProjectManagementTestDataSeeder
```

### Option 2: Add to DatabaseSeeder

Add this to `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    // ... other seeders
    
    $this->call([
        ProjectManagementTestDataSeeder::class,
    ]);
}
```

Then run:
```bash
php artisan db:seed
```

### Option 3: Fresh migration with seeder

To start fresh with test data:

```bash
php artisan migrate:fresh --seed --seeder=ProjectManagementTestDataSeeder
```

## Prerequisites

Before running the seeder, ensure you have:
1. At least one **Organization** in the database
2. At least one **User** associated with that organization

If you don't have these, you can create them first:

```bash
php artisan db:seed --class=TestUserSeeder
```

## What Gets Created

### Projects (15 total)
1. Website Redesign (Active, Urgent, 65% progress)
2. Mobile App Development (Active, High, 45% progress)
3. Marketing Campaign Q1 (Active, Medium, 80% progress)
4. Customer Portal (Active, High, 30% progress)
5. Inventory Management System (Active, Medium, 55% progress)
6. Data Migration Project (Planning, High, 0% progress)
7. Security Audit (Planning, Urgent, 0% progress)
8. Employee Training Program (Planning, Low, 0% progress)
9. Legacy System Upgrade (On Hold, Medium, 25% progress)
10. New Office Setup (On Hold, Low, 10% progress)
11. Q4 Marketing Campaign (Completed, Medium, 100% progress)
12. Website Launch (Completed, High, 100% progress)
13. Payment Gateway Integration (Completed, Urgent, 100% progress)
14. Customer Support Portal (Completed, Medium, 100% progress)
15. Abandoned Feature (Cancelled, Low, 15% progress)

### Tasks
- Each project gets 5-10 tasks
- Tasks are distributed across statuses (todo, in_progress, done)
- Tasks have various priorities and due dates

### Time Entries
- ~30 time entries per project
- Spread over the past 3 months
- Random hours (1-8 hours per entry)

### Budgets
- 2-4 budget categories per project
- Categories: Development, Design, Marketing, Infrastructure, Consulting, Training
- Allocated and spent amounts based on project progress

### Milestones
- 2-5 milestones per project
- Based on project progress percentage
- Completed milestones for projects with higher progress

## Viewing the Data

After seeding, visit:
- **Project Overview**: `/projects/section`
- **All Projects**: `/projects`
- **Project Reports**: `/projects/reports`

The charts will display:
- Project Status Distribution (Pie Chart)
- Project Priority Distribution (Pie Chart)
- Task Status Breakdown (Bar Chart)
- Budget Utilization (Donut Chart)
- Project Progress Trend (Line Chart)

## Notes

- The seeder uses existing organizations and users
- All dates are relative to the current date
- Budget amounts are in the default currency (ZMW)
- Progress percentages are realistic based on project status
- Time entries are distributed to show trends over time

## Troubleshooting

If you encounter errors:

1. **No organization found**: Create an organization first
2. **No users found**: Create users first using TestUserSeeder
3. **Duplicate key errors**: Clear existing project data first:
   ```bash
   php artisan tinker
   >>> App\Models\Project::truncate();
   >>> App\Models\ProjectTask::truncate();
   >>> App\Models\ProjectTimeEntry::truncate();
   >>> App\Models\ProjectBudget::truncate();
   >>> App\Models\ProjectMilestone::truncate();
   >>> App\Models\ProjectMember::truncate();
   ```

