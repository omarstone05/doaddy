import { Head, Link, router, usePage } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ProjectCard } from '@/Components/projects/ProjectCard';
import { ProjectStatsCards, TaskStatsCard, BudgetStatsCard, TimeStatsCard } from '@/Components/projects/ProjectStatsCards';
import { ProjectQuickActions } from '@/Components/projects/ProjectQuickActions';
import { ProjectManagementTabs } from '@/Components/projects/ProjectManagementTabs';
import {
    ProjectStatusChart,
    TaskStatusChart,
    BudgetUtilizationChart,
    ProjectPriorityChart,
    ProjectProgressChart,
} from '@/Components/projects/ProjectCharts';
import { 
    Plus, 
    FolderKanban, 
    CheckCircle2, 
    Clock, 
    AlertTriangle,
    Target,
    DollarSign,
    TrendingUp
} from 'lucide-react';

export default function ProjectsSectionIndex({ stats, recentProjects, insights, chartData }) {
    const { url } = usePage().props;
    const currentPath = (url || (typeof window !== 'undefined' ? window.location.pathname : '')).replace(/\/$/, '') || '/';

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Project Management" />
            
            {/* Project Management Tabs */}
            <ProjectManagementTabs currentPath={currentPath} />
            
            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Project Management</h1>
                    <p className="text-gray-500 mt-1">Manage and track all your projects</p>
                </div>
                <Button onClick={() => router.visit('/projects/create')}>
                    <Plus className="h-4 w-4 mr-2" />
                    New Project
                </Button>
            </div>

            {/* Stats Cards - Using Modular Component */}
            <ProjectStatsCards stats={stats} className="mb-8" />

            {/* Additional Stats Row - Using Modular Components */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <TaskStatsCard 
                    taskStats={{
                        total: stats?.total_tasks || 0,
                        done: stats?.completed_tasks || 0,
                    }} 
                />
                <BudgetStatsCard 
                    totalBudget={stats?.total_budget || 0}
                    totalSpent={stats?.total_spent || 0}
                />
                <TimeStatsCard 
                    totalTime={stats?.total_time || 0}
                />
            </div>

            {/* Charts Section */}
            <div className="mb-8">
                <h2 className="text-xl font-semibold text-gray-900 mb-6">Visual Analytics</h2>
                
                {/* First Row: Status and Priority Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <ProjectStatusChart data={chartData?.statusDistribution || []} />
                    <ProjectPriorityChart data={chartData?.priorityDistribution || []} />
                </div>

                {/* Second Row: Task Status and Budget Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <TaskStatusChart data={chartData?.taskStatusBreakdown || []} />
                    <BudgetUtilizationChart 
                        totalBudget={stats?.total_budget || 0}
                        totalSpent={stats?.total_spent || 0}
                    />
                </div>

                {/* Third Row: Progress Trend Chart (Full Width) */}
                <div className="grid grid-cols-1 gap-6">
                    <ProjectProgressChart data={chartData?.progressTrend || []} />
                </div>
            </div>

            {/* Recent Projects */}
            <div className="mb-8">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-xl font-semibold text-gray-900">Recent Projects</h2>
                    <Link href="/projects" className="text-sm text-blue-600 hover:text-blue-700">
                        View All →
                    </Link>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {recentProjects && recentProjects.length > 0 ? (
                        recentProjects.map((project) => (
                            <ProjectCard key={project.id} project={project} />
                        ))
                    ) : (
                        <div className="col-span-full text-center py-12 text-gray-500">
                            <FolderKanban className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                            <p>No projects yet. Create your first project to get started.</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Quick Actions - Using Modular Component */}
            <ProjectQuickActions showReports={true} className="mt-8" />
        </SectionLayout>
    );
}

