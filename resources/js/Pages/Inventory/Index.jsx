import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { 
    Card, 
    CardHeader,
    StatCard,
} from '@/Components/ui';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { Package, Box, Boxes, AlertTriangle } from 'lucide-react';

export default function InventoryIndex({ stats, insights }) {
    const quickActions = [
        {
            title: 'Add Product',
            description: 'Create product',
            icon: Box,
            href: '/products/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Add Service',
            description: 'Create service',
            icon: Package,
            href: '/products/create?type=service',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Add Asset',
            description: 'Track asset',
            icon: Boxes,
            href: '/assets/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Stock Alert',
            description: 'View low stock',
            icon: AlertTriangle,
            href: '/stock?filter=low',
            iconColor: 'text-teal-500',
        },
    ];

    return (
        <SectionLayout sectionName="Inventory">
            <Head title="Inventory" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="Inventory" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <StatCard
                    title="Total Products"
                    value={stats?.total_products || 0}
                    icon={Box}
                    variant="gradient-positive"
                />
                <StatCard
                    title="Total Services"
                    value={stats?.total_services || 0}
                    icon={Package}
                    variant="glass"
                />
                <StatCard
                    title="Total Assets"
                    value={stats?.total_assets || 0}
                    icon={Boxes}
                    variant="glass"
                />
                <StatCard
                    title="Low Stock Items"
                    value={stats?.low_stock || 0}
                    icon={AlertTriangle}
                    variant={stats?.low_stock > 0 ? 'gradient-negative' : 'glass'}
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
