import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { 
    Card, 
    CardHeader,
    StatCard,
    EmptyState,
} from '@/Components/ui';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { Wallet, TrendingUp, FileText, ArrowUp, ArrowDown, ArrowRight, Plus } from 'lucide-react';

export default function MoneyIndex({ stats, insights }) {
    const formatCurrency = (amount) => {
        const numAmount = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
        }).format(numAmount);
    };

    const formatShort = (amount) => {
        const numAmount = parseFloat(amount) || 0;
        if (numAmount >= 1000000) return `${(numAmount / 1000000).toFixed(1)}M`;
        if (numAmount >= 1000) return `${(numAmount / 1000).toFixed(1)}K`;
        return numAmount.toFixed(0);
    };

    const quickActions = [
        {
            title: 'Add Account',
            description: 'Create money account',
            icon: Wallet,
            href: '/money/accounts/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Record Income',
            description: 'Add income',
            icon: ArrowUp,
            href: '/transactions/create?type=income',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Record Expense',
            description: 'Add expense',
            icon: ArrowDown,
            href: '/transactions/create?type=expense',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Create Budget',
            description: 'Set up budget',
            icon: FileText,
            href: '/money/budgets/create',
            iconColor: 'text-teal-500',
        },
    ];

    return (
        <SectionLayout sectionName="Money">
            <Head title="Money" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="Money" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <StatCard
                    title="Total Accounts"
                    value={stats?.total_accounts || 0}
                    icon={Wallet}
                    variant="glass"
                />
                <StatCard
                    title="Total Balance"
                    value={formatShort(stats?.total_balance)}
                    prefix="K "
                    icon={TrendingUp}
                    variant="gradient-positive"
                />
                <StatCard
                    title="This Month Income"
                    value={formatShort(stats?.monthly_income)}
                    prefix="K "
                    icon={ArrowUp}
                    variant="gradient-positive"
                />
                <StatCard
                    title="This Month Expenses"
                    value={formatShort(stats?.monthly_expenses)}
                    prefix="K "
                    icon={ArrowDown}
                    variant="gradient-negative"
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

            {/* Recent Activity */}
            <Card variant="glass" padding="md">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-bold text-gray-900">Recent Activity</h3>
                    <Link href="/transactions" className="text-sm font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1 group">
                        View all <ArrowRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
                    </Link>
                </div>
                {stats?.recent_movements && stats.recent_movements.length > 0 ? (
                    <div className="space-y-2">
                        {stats.recent_movements.map((movement) => (
                            <div key={movement.id} className="flex items-center justify-between p-3 rounded-xl hover:bg-teal-50/50 transition-colors">
                                <div className="flex items-center gap-3">
                                    <div className={`w-10 h-10 rounded-full flex items-center justify-center ${movement.flow_type === 'income' ? 'bg-teal-100' : 'bg-red-100'}`}>
                                        {movement.flow_type === 'income' ? (
                                            <ArrowUp className="h-5 w-5 text-teal-600" />
                                        ) : (
                                            <ArrowDown className="h-5 w-5 text-red-600" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900">{movement.description}</p>
                                        <p className="text-xs text-gray-500">{movement.account?.name || 'N/A'}</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className={`text-sm font-bold ${movement.flow_type === 'income' ? 'text-teal-600' : 'text-red-600'}`}>
                                        {movement.flow_type === 'income' ? '+' : '-'}{formatCurrency(movement.amount)}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {new Date(movement.transaction_date).toLocaleDateString()}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <EmptyState
                        icon={Wallet}
                        title="No recent activity"
                        description="Start by recording an income or expense"
                    />
                )}
            </Card>
        </SectionLayout>
    );
}
