<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectMilestone;
use App\Models\ProjectTimeEntry;
use App\Models\ProjectBudget;
use App\Models\ProjectMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProjectManagementTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::first();
        
        if (!$organization) {
            $this->command->error('No organization found. Please create an organization first.');
            return;
        }

        $users = User::where('organization_id', $organization->id)->get();
        
        if ($users->isEmpty()) {
            $this->command->error('No users found. Please create users first.');
            return;
        }

        $this->command->info('Creating comprehensive project management test data...');

        // Create projects with varied statuses, priorities, and progress
        $projects = $this->createProjects($organization, $users);
        $this->command->info('✓ Created ' . count($projects) . ' projects');

        // Create tasks for each project
        $totalTasks = 0;
        foreach ($projects as $project) {
            $tasks = $this->createTasksForProject($project, $users);
            $totalTasks += count($tasks);
        }
        $this->command->info('✓ Created ' . $totalTasks . ' tasks');

        // Create milestones
        $totalMilestones = 0;
        foreach ($projects as $project) {
            $milestones = $this->createMilestonesForProject($project);
            $totalMilestones += count($milestones);
        }
        $this->command->info('✓ Created ' . $totalMilestones . ' milestones');

        // Create time entries
        $timeEntries = $this->createTimeEntries($projects, $users);
        $this->command->info('✓ Created ' . count($timeEntries) . ' time entries');

        // Create budget entries
        $budgets = $this->createBudgets($projects);
        $this->command->info('✓ Created ' . count($budgets) . ' budget entries');

        // Create project members
        $members = $this->createProjectMembers($projects, $users);
        $this->command->info('✓ Created ' . count($members) . ' project members');

        $this->command->info('✅ Project management test data seeding completed!');
    }

    private function createProjects($organization, $users): array
    {
        $projects = [];
        
        // Define project templates with varied data for charts
        $projectTemplates = [
            // Active projects with different priorities
            ['name' => 'Website Redesign', 'status' => 'active', 'priority' => 'urgent', 'progress' => 65, 'budget' => 50000, 'spent' => 32000],
            ['name' => 'Mobile App Development', 'status' => 'active', 'priority' => 'high', 'progress' => 45, 'budget' => 75000, 'spent' => 34000],
            ['name' => 'Marketing Campaign Q1', 'status' => 'active', 'priority' => 'medium', 'progress' => 80, 'budget' => 30000, 'spent' => 24000],
            ['name' => 'Customer Portal', 'status' => 'active', 'priority' => 'high', 'progress' => 30, 'budget' => 60000, 'spent' => 18000],
            ['name' => 'Inventory Management System', 'status' => 'active', 'priority' => 'medium', 'progress' => 55, 'budget' => 45000, 'spent' => 24750],
            
            // Planning projects
            ['name' => 'Data Migration Project', 'status' => 'planning', 'priority' => 'high', 'progress' => 0, 'budget' => 40000, 'spent' => 0],
            ['name' => 'Security Audit', 'status' => 'planning', 'priority' => 'urgent', 'progress' => 0, 'budget' => 25000, 'spent' => 0],
            ['name' => 'Employee Training Program', 'status' => 'planning', 'priority' => 'low', 'progress' => 0, 'budget' => 15000, 'spent' => 0],
            
            // On hold projects
            ['name' => 'Legacy System Upgrade', 'status' => 'on_hold', 'priority' => 'medium', 'progress' => 25, 'budget' => 80000, 'spent' => 20000],
            ['name' => 'New Office Setup', 'status' => 'on_hold', 'priority' => 'low', 'progress' => 10, 'budget' => 100000, 'spent' => 10000],
            
            // Completed projects
            ['name' => 'Q4 Marketing Campaign', 'status' => 'completed', 'priority' => 'medium', 'progress' => 100, 'budget' => 35000, 'spent' => 33000],
            ['name' => 'Website Launch', 'status' => 'completed', 'priority' => 'high', 'progress' => 100, 'budget' => 55000, 'spent' => 52000],
            ['name' => 'Payment Gateway Integration', 'status' => 'completed', 'priority' => 'urgent', 'progress' => 100, 'budget' => 20000, 'spent' => 19500],
            ['name' => 'Customer Support Portal', 'status' => 'completed', 'priority' => 'medium', 'progress' => 100, 'budget' => 40000, 'spent' => 38000],
            
            // Cancelled project
            ['name' => 'Abandoned Feature', 'status' => 'cancelled', 'priority' => 'low', 'progress' => 15, 'budget' => 30000, 'spent' => 4500],
        ];

        foreach ($projectTemplates as $index => $template) {
            $startDate = Carbon::now()->subMonths(rand(1, 8));
            $endDate = $startDate->copy()->addMonths(rand(2, 6));
            $targetDate = $template['status'] === 'completed' 
                ? $startDate->copy()->addMonths(rand(2, 4))
                : ($template['status'] === 'cancelled' ? $startDate->copy()->addMonths(1) : $endDate);
            
            $createdAt = $startDate->copy()->subDays(rand(1, 30));
            
            $project = Project::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $template['name'],
                'description' => 'Detailed description for ' . $template['name'] . '. This project involves multiple phases and requires careful planning and execution.',
                'status' => $template['status'],
                'priority' => $template['priority'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'target_completion_date' => $targetDate,
                'progress_percentage' => $template['progress'],
                'project_manager_id' => $users->random()->id,
                'created_by_id' => $users->random()->id,
                'budget' => $template['budget'],
                'spent' => $template['spent'],
                'notes' => 'Project notes and updates for ' . $template['name'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addDays(rand(1, 30)),
            ]);

            $projects[] = $project;
        }

        return $projects;
    }

    private function createTasksForProject($project, $users): array
    {
        $tasks = [];
        $taskTemplates = [
            ['title' => 'Project Planning', 'status' => 'done', 'priority' => 'high'],
            ['title' => 'Requirements Gathering', 'status' => 'done', 'priority' => 'high'],
            ['title' => 'Design Mockups', 'status' => 'done', 'priority' => 'medium'],
            ['title' => 'Development Phase 1', 'status' => 'in_progress', 'priority' => 'urgent'],
            ['title' => 'Development Phase 2', 'status' => 'in_progress', 'priority' => 'high'],
            ['title' => 'Testing', 'status' => 'todo', 'priority' => 'high'],
            ['title' => 'Documentation', 'status' => 'todo', 'priority' => 'medium'],
            ['title' => 'Deployment', 'status' => 'todo', 'priority' => 'urgent'],
            ['title' => 'User Training', 'status' => 'todo', 'priority' => 'low'],
            ['title' => 'Post-Launch Support', 'status' => 'todo', 'priority' => 'medium'],
        ];

        // Adjust tasks based on project progress
        $numTasks = min(count($taskTemplates), max(5, (int)($project->progress_percentage / 10) + 3));
        $selectedTasks = array_slice($taskTemplates, 0, $numTasks);

        foreach ($selectedTasks as $index => $template) {
            $dueDate = $project->start_date->copy()->addDays(rand(10, 90));
            $startDate = $project->start_date->copy()->addDays(rand(1, 30));
            
            $task = ProjectTask::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'title' => $template['title'] . ' - ' . $project->name,
                'description' => 'Task description for ' . $template['title'],
                'status' => $template['status'],
                'priority' => $template['priority'],
                'assigned_to_id' => $users->random()->id,
                'created_by_id' => $project->created_by_id,
                'due_date' => $dueDate,
                'start_date' => $startDate,
                'estimated_hours' => rand(4, 40),
                'actual_hours' => $template['status'] === 'done' ? rand(4, 40) : ($template['status'] === 'in_progress' ? rand(2, 20) : null),
                'order' => $index + 1,
            ]);

            $tasks[] = $task;
        }

        return $tasks;
    }

    private function createMilestonesForProject($project): array
    {
        $milestones = [];
        $milestoneNames = [
            'Project Kickoff',
            'Design Approval',
            'Development Complete',
            'Testing Phase',
            'Launch Ready',
        ];

        $numMilestones = min(count($milestoneNames), max(2, (int)($project->progress_percentage / 20) + 1));

        foreach (array_slice($milestoneNames, 0, $numMilestones) as $index => $name) {
            $targetDate = $project->start_date->copy()->addDays(($index + 1) * 30);
            $isCompleted = $project->progress_percentage >= (($index + 1) * 20);

            $milestone = ProjectMilestone::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'name' => $name,
                'description' => 'Milestone: ' . $name,
                'target_date' => $targetDate,
                'completed_date' => $isCompleted ? $targetDate->copy()->subDays(rand(0, 5)) : null,
                'status' => $isCompleted ? 'completed' : 'pending',
                'order' => $index + 1,
            ]);

            $milestones[] = $milestone;
        }

        return $milestones;
    }

    private function createTimeEntries($projects, $users): array
    {
        $timeEntries = [];
        
        foreach ($projects as $project) {
            $tasks = $project->tasks;
            if ($tasks->isEmpty()) {
                continue;
            }

            // Create time entries for the past 3 months
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::now()->subDays($i);
                
                // Skip weekends occasionally
                if (rand(1, 10) <= 2 && ($date->isWeekend())) {
                    continue;
                }

                $task = $tasks->random();
                $hours = rand(1, 8);
                
                $timeEntry = ProjectTimeEntry::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'task_id' => $task->id,
                    'user_id' => $users->random()->id,
                    'date' => $date,
                    'hours' => $hours,
                    'description' => 'Worked on ' . $task->title,
                ]);

                $timeEntries[] = $timeEntry;
            }
        }

        return $timeEntries;
    }

    private function createBudgets($projects): array
    {
        $budgets = [];
        $budgetCategories = [
            'Development',
            'Design',
            'Marketing',
            'Infrastructure',
            'Consulting',
            'Training',
        ];

        foreach ($projects as $project) {
            // Create 2-4 budget entries per project
            $numBudgets = rand(2, 4);
            $totalAllocated = 0;
            $totalSpent = 0;

            foreach (array_slice($budgetCategories, 0, $numBudgets) as $category) {
                $allocated = rand(5000, 20000);
                $spent = (int)($allocated * ($project->progress_percentage / 100) * rand(80, 120) / 100);
                $spent = min($spent, $allocated); // Don't exceed allocated

                $totalAllocated += $allocated;
                $totalSpent += $spent;

                $budget = ProjectBudget::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'name' => $category . ' Budget',
                    'category' => $category,
                    'allocated_amount' => $allocated,
                    'spent_amount' => $spent,
                    'description' => 'Budget for ' . $category . ' in ' . $project->name,
                ]);

                $budgets[] = $budget;
            }
        }

        return $budgets;
    }

    private function createProjectMembers($projects, $users): array
    {
        $members = [];

        foreach ($projects as $project) {
            // Add 2-4 members to each project, but not more than available users
            $availableUsers = $users->filter(function($user) use ($project) {
                return $user->id !== $project->project_manager_id;
            });
            
            if ($availableUsers->isEmpty()) {
                continue;
            }
            
            $numMembers = min(rand(2, 4), $availableUsers->count());
            $selectedUsers = $availableUsers->random($numMembers);

            foreach ($selectedUsers as $user) {
                $member = ProjectMember::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'role' => ['Developer', 'Designer', 'Tester', 'Analyst'][rand(0, 3)],
                ]);

                $members[] = $member;
            }
        }

        return $members;
    }
}

