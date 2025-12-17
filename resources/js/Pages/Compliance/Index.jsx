import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { 
    Card, 
    CardHeader,
    StatCard,
} from '@/Components/ui';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { Shield, FileText, FileCheck, Building2 } from 'lucide-react';

export default function ComplianceIndex({ stats, insights }) {
    const quickActions = [
        {
            title: 'Add Document',
            description: 'Upload document',
            icon: FileText,
            href: '/compliance/documents/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Add License',
            description: 'Register license',
            icon: FileCheck,
            href: '/compliance/licenses/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Audit Trail',
            description: 'View activity',
            icon: Shield,
            href: '/activity-logs',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Settings',
            description: 'Manage settings',
            icon: Building2,
            href: '/settings',
            iconColor: 'text-teal-500',
        },
    ];

    return (
        <SectionLayout sectionName="Compliance">
            <Head title="Compliance" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="Compliance" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <StatCard
                    title="Documents"
                    value={stats?.total_documents || 0}
                    icon={FileText}
                    variant="gradient-positive"
                />
                <StatCard
                    title="Active Licenses"
                    value={stats?.active_licenses || 0}
                    icon={FileCheck}
                    variant="glass"
                />
                <StatCard
                    title="Expiring Soon"
                    value={stats?.expiring_soon || 0}
                    icon={Shield}
                    variant={stats?.expiring_soon > 0 ? 'gradient-negative' : 'glass'}
                />
                <StatCard
                    title="Audit Logs"
                    value={stats?.audit_logs || 0}
                    icon={Shield}
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
