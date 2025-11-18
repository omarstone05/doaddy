<?php

namespace App\Modules\Consulting\Cards;

use App\Services\Dashboard\CardRegistry;

/**
 * Consulting Module Card Definitions
 * 
 * Dashboard cards for the Consulting module
 */
class ConsultingCards
{
    public static function register(): void
    {
        // Register the Consulting module
        CardRegistry::registerModule('consulting', [
            'name' => 'Consulting',
            'description' => 'Project and task management for consulting businesses',
            'icon' => 'Briefcase',
            'color' => '#00635D', // Addy teal
        ]);

        // Register all Consulting cards
        self::registerActiveProjectsCard();
        self::registerProjectHealthCard();
        self::registerTaskCompletionCard();
        self::registerUpcomingDeadlinesCard();
        self::registerProjectProgressCard();
    }

    protected static function registerActiveProjectsCard(): void
    {
        CardRegistry::register('consulting', [
            'id' => 'consulting.active_projects',
            'name' => 'Active Projects',
            'description' => 'Number of currently active consulting projects',
            'component' => 'ActiveProjectsCard',
            'category' => 'metric',
            'size' => 'small',
            'icon' => 'Briefcase',
            'color' => '#00635D',
            'priority' => 10,
            'suitable_for' => ['consulting', 'general'],
            'tags' => 'projects active consulting',
            'data_endpoint' => '/api/dashboard/card-data/consulting.active_projects',
            'refresh_interval' => 300,
        ]);
    }

    protected static function registerProjectHealthCard(): void
    {
        CardRegistry::register('consulting', [
            'id' => 'consulting.project_health',
            'name' => 'Project Health',
            'description' => 'Overview of project health status',
            'component' => 'ProjectHealthCard',
            'category' => 'chart',
            'size' => 'medium',
            'icon' => 'Activity',
            'color' => '#00635D',
            'priority' => 9,
            'suitable_for' => ['consulting', 'general'],
            'tags' => 'projects health status consulting',
            'data_endpoint' => '/api/dashboard/card-data/consulting.project_health',
            'refresh_interval' => 300,
        ]);
    }

    protected static function registerTaskCompletionCard(): void
    {
        CardRegistry::register('consulting', [
            'id' => 'consulting.task_completion',
            'name' => 'Task Completion',
            'description' => 'Task completion rate across all projects',
            'component' => 'TaskCompletionCard',
            'category' => 'chart',
            'size' => 'medium',
            'icon' => 'CheckSquare',
            'color' => '#00635D',
            'priority' => 8,
            'suitable_for' => ['consulting', 'general'],
            'tags' => 'tasks completion progress consulting',
            'data_endpoint' => '/api/dashboard/card-data/consulting.task_completion',
            'refresh_interval' => 300,
        ]);
    }

    protected static function registerUpcomingDeadlinesCard(): void
    {
        CardRegistry::register('consulting', [
            'id' => 'consulting.upcoming_deadlines',
            'name' => 'Upcoming Deadlines',
            'description' => 'Tasks and projects with approaching deadlines',
            'component' => 'UpcomingDeadlinesCard',
            'category' => 'list',
            'size' => 'medium',
            'icon' => 'Calendar',
            'color' => '#00635D',
            'priority' => 7,
            'suitable_for' => ['consulting', 'general'],
            'tags' => 'deadlines tasks projects consulting',
            'data_endpoint' => '/api/dashboard/card-data/consulting.upcoming_deadlines',
            'refresh_interval' => 180,
        ]);
    }

    protected static function registerProjectProgressCard(): void
    {
        CardRegistry::register('consulting', [
            'id' => 'consulting.project_progress',
            'name' => 'Project Progress',
            'description' => 'Overall progress across all active projects',
            'component' => 'ProjectProgressCard',
            'category' => 'chart',
            'size' => 'large',
            'icon' => 'TrendingUp',
            'color' => '#00635D',
            'priority' => 6,
            'suitable_for' => ['consulting', 'general'],
            'tags' => 'projects progress consulting',
            'data_endpoint' => '/api/dashboard/card-data/consulting.project_progress',
            'refresh_interval' => 300,
        ]);
    }
}

