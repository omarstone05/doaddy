<?php

namespace Database\Seeders;

use App\Models\OrganizationRole;
use Illuminate\Database\Seeder;

class ProjectManagementRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Project Manager',
                'slug' => 'project_manager',
                'description' => 'Full access to project management features. Can create, edit, and manage all projects, tasks, and team members.',
                'permissions' => [
                    'projects.view',
                    'projects.create',
                    'projects.edit',
                    'projects.delete',
                    'projects.assign',
                    'tasks.view',
                    'tasks.create',
                    'tasks.edit',
                    'tasks.delete',
                    'tasks.assign',
                    'tasks.view_granular',
                    'milestones.view',
                    'milestones.create',
                    'milestones.edit',
                    'milestones.delete',
                    'team.view',
                    'team.manage',
                    'budget.view',
                    'budget.manage',
                    'time.view',
                    'time.manage',
                    'reports.view',
                ],
                'level' => 70,
                'is_system' => true,
            ],
            [
                'name' => 'Project Coordinator',
                'slug' => 'project_coordinator',
                'description' => 'Can coordinate projects and manage tasks. Limited project creation and deletion permissions.',
                'permissions' => [
                    'projects.view',
                    'projects.create',
                    'projects.edit',
                    'tasks.view',
                    'tasks.create',
                    'tasks.edit',
                    'tasks.assign',
                    'tasks.view_granular',
                    'milestones.view',
                    'milestones.create',
                    'milestones.edit',
                    'team.view',
                    'budget.view',
                    'time.view',
                    'time.manage',
                    'reports.view',
                ],
                'level' => 55,
                'is_system' => true,
            ],
            [
                'name' => 'Task Assignee',
                'slug' => 'task_assignee',
                'description' => 'Can view and manage assigned tasks with granular detail. Limited to own tasks and assigned projects.',
                'permissions' => [
                    'projects.view',
                    'tasks.view',
                    'tasks.edit', // Only own tasks
                    'tasks.view_granular',
                    'milestones.view',
                    'time.view',
                    'time.create', // Only own time entries
                ],
                'level' => 35,
                'is_system' => true,
            ],
            [
                'name' => 'Project Viewer',
                'slug' => 'project_viewer',
                'description' => 'Read-only access to projects and tasks. Can view granular task details but cannot make changes.',
                'permissions' => [
                    'projects.view',
                    'tasks.view',
                    'tasks.view_granular',
                    'milestones.view',
                    'time.view',
                    'reports.view',
                ],
                'level' => 25,
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            OrganizationRole::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }

        $this->command->info('✓ Created ' . count($roles) . ' project management roles');
    }
}

