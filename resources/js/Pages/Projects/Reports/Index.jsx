import { Head, usePage } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ReportCardsGrid } from '@/Components/reports/ReportCard';
import { ProjectManagementTabs } from '@/Components/projects/ProjectManagementTabs';
import { BarChart3, TrendingUp, Clock, DollarSign } from 'lucide-react';

export default function ProjectReportsIndex() {
    const { url } = usePage().props;
    const currentPath = (url || (typeof window !== 'undefined' ? window.location.pathname : '')).replace(/\/$/, '') || '/';
    const reports = [
        {
            id: 'performance',
            name: 'Performance Report',
            description: 'View project performance metrics, task completion rates, and milestone progress',
            icon: TrendingUp,
            href: '/projects/reports/performance',
            color: 'text-blue-500',
            bgColor: 'bg-blue-50',
        },
        {
            id: 'time-tracking',
            name: 'Time Tracking Report',
            description: 'Analyze time spent on projects, by user, and by task',
            icon: Clock,
            href: '/projects/reports/time-tracking',
            color: 'text-green-500',
            bgColor: 'bg-green-50',
        },
        {
            id: 'budget',
            name: 'Budget Report',
            description: 'Track budget utilization, spending, and remaining budget across projects',
            icon: DollarSign,
            href: '/projects/reports/budget',
            color: 'text-amber-500',
            bgColor: 'bg-amber-50',
        },
    ];

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Project Reports" />
            <div>
                {/* Project Management Tabs */}
                <ProjectManagementTabs currentPath={currentPath} />
                
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">Project Reports</h1>
                    <p className="text-gray-500 mt-1">Comprehensive reports and analytics for your projects</p>
                </div>

                {/* Using Modular Report Cards Grid Component */}
                <ReportCardsGrid reports={reports} />
            </div>
        </SectionLayout>
    );
}
