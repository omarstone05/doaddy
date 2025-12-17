<?php

namespace App\Modules\ProjectManagement\Cards;

use App\Services\Dashboard\CardRegistry;

/**
 * Project Management Module Card Definitions
 * 
 * Example of how custom modules contribute their own cards
 */
class ProjectManagementCards
{
    public static function register(): void
    {
        // Register the Project Management module
        CardRegistry::registerModule('project_management', [
            'name' => 'Project Management',
            'description' => 'Track projects, tasks, and team progress',
            'icon' => 'Briefcase',
            'color' => '#A78BFA',
        ]);

        // Register all PM cards
        self::registerActiveProjectsCard();
        self::registerTasksOverviewCard();
        self::registerUpcomingDeadlinesCard();
        self::registerTeamWorkloadCard();
        self::registerProjectTimelineCard();
        self::registerTaskCompletionCard();
    }

    protected static function registerActiveProjectsCard(): void
    {
        CardRegistry::register('project_management', [
            'id' => 'pm.active_projects',
            'name' => 'Active Projects',
            'description' => 'Currently running projects',
            'component' => 'ActiveProjectsCard',
            'category' => 'metric',
            'size' => 'small',
            'icon' => 'Briefcase',
            'color' => '#A78BFA',
            'priority' => 10,
            'suitable_for' => ['consulting', 'general'],
            'tags' => 'projects active count',
            'data_endpoint' => '/api/dashboard/active-projects',
            'refresh_interval' => 300,
        ]);
    }

    protected static function registerTasksOverviewCard(): void
    {
        CardRegistry::register('project_management', [
            'id' => 'pm.tasks_overview',
            'name' => 'Tasks Overview',
            'description' => 'Pending, in progress, and completed tasks',
            'component' => 'TasksOverviewCard',
            'category' => 'metric',
            'size' => 'small',
            'icon' => 'CheckSquare',
            'color' => '#A78BFA',
            'priority' => 9,
            'suitable_for' => ['consulting', 'general'],
            'tags' => 'tasks todo checklist',
            'data_endpoint' => '/api/dashboard/tasks-overview',
            'refresh_interval' => 120,
        ]);
    }

    protected static function registerUpcomingDeadlinesCard(): void
    {
        CardRegistry::register('project_management', [
            'id' => 'pm.upcoming_deadlines',
            'name' => 'Upcoming Deadlines',
            'description' => 'Tasks and projects due soon',
            'component' => 'UpcomingDeadlinesCard',
            'category' => 'list',
            'size' => 'medium',
            'icon' => 'Clock',
            'color' => '#F59E0B',
            'priority' => 10,
            'suitable_for' => ['consulting', 'general'],
            'tags' => 'deadlines due urgent',
            'data_endpoint' => '/api/dashboard/upcoming-deadlines',
            'refresh_interval' => 120,
        ]);
    }

    protected static function registerTeamWorkloadCard(): void
    {
        CardRegistry::register('project_management', [
            'id' => 'pm.team_workload',
            'name' => 'Team Workload',
            'description' => 'Task distribution across team members',
            'component' => 'TeamWorkloadCard',
            'category' => 'chart',
            'size' => 'medium',
            'icon' => 'Users',
            'color' => '#A78BFA',
            'priority' => 6,
            'suitable_for' => ['consulting'],
            'tags' => 'team workload distribution',
            'data_endpoint' => '/api/dashboard/team-workload',
            'refresh_interval' => 600,
        ]);
    }

    protected static function registerProjectTimelineCard(): void
    {
        CardRegistry::register('project_management', [
            'id' => 'pm.project_timeline',
            'name' => 'Project Timeline',
            'description' => 'Gantt-style view of project schedule',
            'component' => 'ProjectTimelineCard',
            'category' => 'chart',
            'size' => 'wide',
            'icon' => 'Calendar',
            'color' => '#A78BFA',
            'priority' => 7,
            'suitable_for' => ['consulting'],
            'tags' => 'timeline gantt schedule',
            'data_endpoint' => '/api/dashboard/project-timeline',
            'refresh_interval' => 600,
        ]);
    }

    protected static function registerTaskCompletionCard(): void
    {
        CardRegistry::register('project_management', [
            'id' => 'pm.task_completion',
            'name' => 'Task Completion Rate',
            'description' => 'How fast you complete tasks',
            'component' => 'TaskCompletionCard',
            'category' => 'progress',
            'size' => 'medium',
            'icon' => 'TrendingUp',
            'color' => '#7DCD85',
            'priority' => 5,
            'suitable_for' => ['consulting', 'general'],
            'tags' => 'completion rate productivity',
            'data_endpoint' => '/api/dashboard/task-completion',
            'refresh_interval' => 600,
        ]);
    }
}

