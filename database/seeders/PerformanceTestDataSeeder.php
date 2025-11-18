<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectMember;
use App\Models\ProjectTimeEntry;
use App\Models\ProjectBudget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class PerformanceTestDataSeeder extends Seeder
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

        $this->command->info('Creating 10 users and 10 projects for performance testing...');

        // Create 10 users
        $users = $this->createUsers($organization);
        $this->command->info('✓ Created ' . count($users) . ' users');

        // Create 10 projects
        $projects = $this->createProjects($organization, $users);
        $this->command->info('✓ Created ' . count($projects) . ' projects');

        // Assign users to projects as members
        $members = $this->assignUsersToProjects($projects, $users);
        $this->command->info('✓ Created ' . count($members) . ' project memberships');

        // Create tasks for each project
        $totalTasks = 0;
        foreach ($projects as $project) {
            $tasks = $this->createTasksForProject($project, $users);
            $totalTasks += count($tasks);
        }
        $this->command->info('✓ Created ' . $totalTasks . ' tasks');

        // Create time entries
        $timeEntries = $this->createTimeEntries($projects, $users);
        $this->command->info('✓ Created ' . count($timeEntries) . ' time entries');

        // Create budgets
        $budgets = $this->createBudgets($projects);
        $this->command->info('✓ Created ' . count($budgets) . ' budget entries');

        $this->command->info('✅ Performance test data seeding completed!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('- Users: ' . count($users));
        $this->command->info('- Projects: ' . count($projects));
        $this->command->info('- Tasks: ' . $totalTasks);
        $this->command->info('- Time Entries: ' . count($timeEntries));
        $this->command->info('- Budget Entries: ' . count($budgets));
    }

    private function createUsers($organization): array
    {
        $users = [];
        $firstNames = ['John', 'Sarah', 'Michael', 'Emily', 'David', 'Jessica', 'James', 'Amanda', 'Robert', 'Lisa'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
        $roles = ['project_manager', 'project_coordinator', 'task_assignee', 'project_viewer'];

        for ($i = 0; $i < 10; $i++) {
            $firstName = $firstNames[$i];
            $lastName = $lastNames[$i];
            $email = strtolower($firstName . '.' . $lastName . $i . '@example.com');
            
            // Check if user already exists
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $users[] = $existingUser;
                continue;
            }

            $user = User::create([
                'id' => (string) Str::uuid(),
                'name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'password' => Hash::make('password'),
                'organization_id' => $organization->id,
                'email_verified_at' => Carbon::now(),
            ]);

            // Assign a random project management role
            $role = $roles[array_rand($roles)];
            $user->assignRoleInOrganization($organization->id, $role);

            $users[] = $user;
        }

        return $users;
    }

    private function createProjects($organization, $users): array
    {
        $projects = [];
        $projectNames = [
            'E-Commerce Platform Development',
            'Mobile Banking Application',
            'Customer Relationship Management System',
            'Inventory Management Solution',
            'Marketing Automation Platform',
            'Data Analytics Dashboard',
            'Cloud Migration Project',
            'Security Infrastructure Upgrade',
            'API Gateway Implementation',
            'Content Management System',
        ];

        $statuses = ['planning', 'active', 'active', 'active', 'on_hold', 'active', 'active', 'completed', 'active', 'active'];
        $priorities = ['urgent', 'high', 'medium', 'high', 'low', 'medium', 'high', 'medium', 'urgent', 'medium'];
        $progressLevels = [0, 25, 45, 60, 30, 75, 50, 100, 35, 80];

        foreach ($projectNames as $index => $name) {
            $startDate = Carbon::now()->subMonths(rand(1, 8));
            $endDate = $startDate->copy()->addMonths(rand(3, 8));
            $targetDate = $statuses[$index] === 'completed' 
                ? $startDate->copy()->addMonths(rand(2, 4))
                : $endDate;
            
            $budget = rand(50000, 200000);
            $spent = (int)($budget * ($progressLevels[$index] / 100) * rand(80, 120) / 100);
            $spent = min($spent, $budget);

            $project = Project::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => $name,
                'description' => 'Comprehensive project description for ' . $name . '. This project involves multiple phases including planning, development, testing, and deployment. The project aims to deliver a high-quality solution that meets all business requirements and user expectations.',
                'status' => $statuses[$index],
                'priority' => $priorities[$index],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'target_completion_date' => $targetDate,
                'progress_percentage' => $progressLevels[$index],
                'project_manager_id' => $users[rand(0, count($users) - 1)]->id,
                'created_by_id' => $users[rand(0, count($users) - 1)]->id,
                'budget' => $budget,
                'spent' => $spent,
                'notes' => 'Project notes and updates for ' . $name,
                'created_at' => $startDate->copy()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 7)),
            ]);

            $projects[] = $project;
        }

        return $projects;
    }

    private function assignUsersToProjects($projects, $users): array
    {
        $members = [];

        foreach ($projects as $project) {
            // Assign 3-5 random users to each project
            $numMembers = rand(3, min(5, count($users)));
            $selectedUsers = collect($users)->random($numMembers);

            foreach ($selectedUsers as $user) {
                // Skip if user is already the project manager
                if ($user->id === $project->project_manager_id) {
                    continue;
                }

                // Check if membership already exists
                $existing = ProjectMember::where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existing) {
                    continue;
                }

                $member = ProjectMember::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'role' => ['manager', 'member', 'viewer', 'contributor'][rand(0, 3)],
                    'joined_at' => $project->start_date->copy()->addDays(rand(0, 30)),
                ]);

                $members[] = $member;
            }
        }

        return $members;
    }

    private function createTasksForProject($project, $users): array
    {
        $tasks = [];
        $taskTemplates = [
            ['title' => 'Project Planning & Requirements', 'status' => 'done', 'priority' => 'high'],
            ['title' => 'System Architecture Design', 'status' => 'done', 'priority' => 'high'],
            ['title' => 'Database Schema Design', 'status' => 'done', 'priority' => 'medium'],
            ['title' => 'Frontend Development - Phase 1', 'status' => 'in_progress', 'priority' => 'urgent'],
            ['title' => 'Backend API Development', 'status' => 'in_progress', 'priority' => 'high'],
            ['title' => 'User Authentication System', 'status' => 'in_progress', 'priority' => 'high'],
            ['title' => 'Payment Integration', 'status' => 'todo', 'priority' => 'high'],
            ['title' => 'Testing & QA', 'status' => 'todo', 'priority' => 'medium'],
            ['title' => 'Documentation', 'status' => 'todo', 'priority' => 'low'],
            ['title' => 'Deployment & Launch', 'status' => 'todo', 'priority' => 'urgent'],
            ['title' => 'Performance Optimization', 'status' => 'todo', 'priority' => 'medium'],
            ['title' => 'Security Audit', 'status' => 'todo', 'priority' => 'high'],
        ];

        // Create 8-12 tasks per project
        $numTasks = rand(8, 12);
        $selectedTasks = array_slice($taskTemplates, 0, $numTasks);

        foreach ($selectedTasks as $index => $template) {
            $dueDate = $project->start_date->copy()->addDays(rand(10, 120));
            $startDate = $project->start_date->copy()->addDays(rand(1, 60));
            $assignedUser = $users[rand(0, count($users) - 1)];

            $task = ProjectTask::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'title' => $template['title'] . ' - ' . $project->name,
                'description' => 'Detailed task description for ' . $template['title'] . '. This task involves multiple steps and requires careful execution to meet project requirements.',
                'status' => $template['status'],
                'priority' => $template['priority'],
                'assigned_to_id' => $assignedUser->id,
                'created_by_id' => $project->created_by_id,
                'due_date' => $dueDate,
                'start_date' => $startDate,
                'estimated_hours' => rand(8, 40),
                'actual_hours' => $template['status'] === 'done' ? rand(8, 40) : ($template['status'] === 'in_progress' ? rand(4, 20) : null),
                'order' => $index + 1,
                'tags' => ['development', 'frontend', 'backend', 'testing', 'deployment'][rand(0, 4)] ? [['development', 'frontend', 'backend', 'testing', 'deployment'][rand(0, 4)]] : [],
            ]);

            $tasks[] = $task;
        }

        return $tasks;
    }

    private function createTimeEntries($projects, $users): array
    {
        $timeEntries = [];
        
        foreach ($projects as $project) {
            $tasks = $project->tasks;
            if ($tasks->isEmpty()) {
                continue;
            }

            // Create time entries for the past 2 months
            for ($i = 0; $i < 40; $i++) {
                $date = Carbon::now()->subDays($i);
                
                // Skip weekends occasionally
                if (rand(1, 10) <= 3 && ($date->isWeekend())) {
                    continue;
                }

                $task = $tasks->random();
                $hours = rand(1, 8);
                
                $timeEntry = ProjectTimeEntry::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'task_id' => $task->id,
                    'user_id' => $users[rand(0, count($users) - 1)]->id,
                    'date' => $date,
                    'hours' => $hours,
                    'description' => 'Worked on ' . $task->title,
                    'is_billable' => rand(0, 1) === 1,
                    'billable_rate' => rand(50, 150),
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
            'Testing',
            'Deployment',
        ];

        foreach ($projects as $project) {
            // Create 3-5 budget entries per project
            $numBudgets = rand(3, 5);
            $selectedCategories = collect($budgetCategories)->random($numBudgets);

            foreach ($selectedCategories as $category) {
                $allocated = rand(5000, 30000);
                $spent = (int)($allocated * ($project->progress_percentage / 100) * rand(80, 120) / 100);
                $spent = min($spent, $allocated);

                $budget = ProjectBudget::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'name' => $category . ' Budget',
                    'category' => $category,
                    'allocated_amount' => $allocated,
                    'spent_amount' => $spent,
                    'description' => 'Budget allocation for ' . $category . ' in ' . $project->name,
                ]);

                $budgets[] = $budget;
            }
        }

        return $budgets;
    }
}

