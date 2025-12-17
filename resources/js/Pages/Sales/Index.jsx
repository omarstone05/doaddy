import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { 
    Card, 
    CardHeader,
    StatCard,
    Badge,
    EmptyState,
} from '@/Components/ui';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { Users, ShoppingCart, FileText, DollarSign, Receipt, ArrowRight } from 'lucide-react';

export default function SalesIndex({ stats, insights }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
        }).format(amount || 0);
    };

    const formatShort = (amount) => {
        if (amount >= 1000000) return `${(amount / 1000000).toFixed(1)}M`;
        if (amount >= 1000) return `${(amount / 1000).toFixed(1)}K`;
        return amount?.toFixed(0) || '0';
    };

    const quickActions = [
        {
            title: 'Add Customer',
            description: 'New customer',
            icon: Users,
            href: '/customers/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Create Quote',
            description: 'Generate quote',
            icon: FileText,
            href: '/quotes/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Create Invoice',
            description: 'Generate invoice',
            icon: FileText,
            href: '/invoices/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Record Payment',
            description: 'Add payment',
            icon: DollarSign,
            href: '/payments/create',
            iconColor: 'text-teal-500',
        },
    ];

    return (
        <SectionLayout sectionName="Sales">
            <Head title="Sales" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="Sales" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <StatCard
                    title="Total Customers"
                    value={stats?.total_customers || 0}
                    icon={Users}
                    variant="glass"
                />
                <StatCard
                    title="Monthly Sales"
                    value={formatShort(stats?.monthly_sales)}
                    prefix="K "
                    icon={ShoppingCart}
                    variant="gradient-positive"
                />
                <StatCard
                    title="Pending Invoices"
                    value={stats?.pending_invoices || 0}
                    icon={FileText}
                    variant={stats?.pending_invoices > 0 ? 'gradient-negative' : 'glass'}
                />
                <StatCard
                    title="Pending Quotes"
                    value={stats?.pending_quotes || 0}
                    icon={FileText}
                    variant="glass"
                />
            </div>

            {/* Quick Actions */}
            <Card variant="glass" padding="md" className="mb-8">
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

            {/* Recent Sales */}
            <Card variant="glass" padding="md">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-bold text-gray-900">Recent Sales</h3>
                    <Link href="/sales" className="text-sm font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1 group">
                        View all <ArrowRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
                    </Link>
                </div>
                {stats?.recent_sales && stats.recent_sales.length > 0 ? (
                    <div className="space-y-2">
                        {stats.recent_sales.map((sale) => (
                            <Link 
                                key={sale.id} 
                                href={`/sales/${sale.id}`} 
                                className="flex items-center justify-between p-3 rounded-xl hover:bg-teal-50/50 transition-colors group"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center">
                                        <Receipt className="h-5 w-5 text-teal-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900 group-hover:text-teal-600 transition-colors">{sale.sale_number}</p>
                                        <p className="text-xs text-gray-500">{sale.customer?.name || 'Walk-in Customer'}</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-bold text-gray-900">{formatCurrency(sale.total_amount)}</p>
                                    <p className="text-xs text-gray-500">
                                        {new Date(sale.created_at).toLocaleDateString()}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <EmptyState
                        icon={ShoppingCart}
                        title="No recent sales"
                        description="Start by recording your first sale"
                    />
                )}
            </Card>
        </SectionLayout>
    );
}
