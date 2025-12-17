import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { 
    Card, 
    CardHeader,
    StatCard,
} from '@/Components/ui';
import { DecisionsInsightCard } from '@/Components/sections/DecisionsInsightCard';
import { Target, BarChart3, FolderKanban, TrendingUp } from 'lucide-react';

export default function DecisionsIndex({ stats, insights }) {
    const quickActions = [
        {
            title: 'Create OKR',
            description: 'Set objectives',
            icon: Target,
            href: '/decisions/okrs/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'New Project',
            description: 'Start project',
            icon: FolderKanban,
            href: '/projects/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Strategic Goal',
            description: 'Define goals',
            icon: TrendingUp,
            href: '/decisions/goals/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'View Reports',
            description: 'AI insights',
            icon: BarChart3,
            href: '/reports',
            iconColor: 'text-teal-500',
        },
    ];

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Decisions" />
            
            {/* Addy Insights Card - White with Mint Gradient */}
            <DecisionsInsightCard 
                sectionName="Decisions" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <StatCard
                    title="Active OKRs"
                    value={stats?.active_okrs || 0}
                    icon={Target}
                    variant="gradient-positive"
                />
                <StatCard
                    title="Active Projects"
                    value={stats?.active_projects || 0}
                    icon={FolderKanban}
                    variant="glass"
                />
                <StatCard
                    title="Strategic Goals"
                    value={stats?.strategic_goals || 0}
                    icon={TrendingUp}
                    variant="glass"
                />
                <StatCard
                    title="Reports"
                    value={stats?.reports || 0}
                    icon={BarChart3}
                    variant="glass"
                />
            </div>

            {/* Quick Actions */}
            <Card variant="glass" padding="md">
                <CardHeader label="Quick Actions" />
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    {quickActions.map((action) => (
                        <Link
                            key={action.title}
                            href={action.href}
                            className="group flex flex-col items-center py-5 px-3 rounded-xl hover:bg-teal-50/50 transition-all duration-200"
                        >
                            <div className="mb-3">
                                <action.icon className={`w-8 h-8 ${action.iconColor} group-hover:scale-110 transition-transform duration-200`} strokeWidth={1.5} />
                            </div>
                            <p className="text-sm font-semibold text-gray-900 text-center group-hover:text-teal-700 transition-colors">{action.title}</p>
                            <p className="text-xs text-gray-500 text-center mt-0.5">{action.description}</p>
                        </Link>
                    ))}
                </div>
            </Card>
        </SectionLayout>
    );
}
