import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Card, 
    CardHeader,
    StatCard,
    Badge,
    EmptyState,
} from '@/Components/ui';
import XPCard from '@/Components/Addy/Gamification/XPCard';
import XPNotification from '@/Components/Addy/Gamification/XPNotification';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from 'recharts';
import {
    ShoppingCart,
    Receipt,
    TrendingUp,
    DollarSign,
    FileText,
    ArrowRight,
    Plus,
    CheckCircle,
    BarChart3,
    Package,
} from 'lucide-react';

// Time Period Selector Component
const TimePeriodSelector = ({ selected, onChange }) => {
    const periods = [
        { value: 'week', label: 'Week' },
        { value: 'month', label: 'Month' },
        { value: 'year', label: 'Year' },
    ];

    return (
        <div className="inline-flex items-center bg-gray-100/80 rounded-xl p-1 backdrop-blur-sm">
            {periods.map((period) => (
                <button
                    key={period.value}
                    onClick={() => onChange(period.value)}
                    className={`px-4 py-1.5 text-sm font-semibold rounded-lg transition-all duration-200 ${
                        selected === period.value
                            ? 'bg-white text-teal-600 shadow-sm'
                            : 'text-gray-600 hover:text-gray-900'
                    }`}
                >
                    {period.label}
                </button>
            ))}
        </div>
    );
};

// Quick Actions Component - Projjo-inspired style
const QuickActions = () => {
    const actions = [
        {
            title: 'New Sale',
            description: 'Create invoice',
            icon: Plus,
            href: '/invoices/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Add Product',
            description: 'Create product',
            icon: Package,
            href: '/products/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Create Bill',
            description: 'Record expense',
            icon: Receipt,
            href: '/bills/create',
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
        <Card variant="glass" padding="md">
            <CardHeader label="Quick Actions" />
            <div className="grid grid-cols-2 gap-4">
                {actions.map((action) => (
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
    );
};

// Cash Flow Chart Component - Using Card from UI library
const CashFlowChart = ({ data, period }) => {
    const chartData = data || [
        { month: 'Jan', revenue: 45, expenses: 30 },
        { month: 'Feb', revenue: 52, expenses: 35 },
        { month: 'Mar', revenue: 48, expenses: 32 },
        { month: 'Apr', revenue: 61, expenses: 40 },
        { month: 'May', revenue: 55, expenses: 38 },
        { month: 'Jun', revenue: 67, expenses: 42 },
        { month: 'Jul', revenue: 72, expenses: 45 },
        { month: 'Aug', revenue: 78, expenses: 48 },
        { month: 'Sep', revenue: 82, expenses: 52 },
        { month: 'Oct', revenue: 88, expenses: 55 },
        { month: 'Nov', revenue: 95, expenses: 58 },
        { month: 'Dec', revenue: 102, expenses: 62 },
    ];

    return (
        <Card variant="glass" padding="md">
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h3 className="text-lg font-bold text-gray-900">Cash Flow</h3>
                    <p className="text-sm text-gray-500">Revenue vs Expenses</p>
                </div>
                <div className="flex gap-4 text-sm">
                    <div className="flex items-center gap-2">
                        <div className="w-3 h-3 rounded-full bg-teal-500" />
                        <span className="text-gray-600">Revenue</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="w-3 h-3 rounded-full bg-orange-400" />
                        <span className="text-gray-600">Expenses</span>
                    </div>
                </div>
            </div>
            <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={chartData} barGap={4}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#e7e5e4" vertical={false} />
                        <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fill: '#78716c', fontSize: 12 }} />
                        <YAxis axisLine={false} tickLine={false} tick={{ fill: '#78716c', fontSize: 12 }} />
                        <Tooltip
                            contentStyle={{
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                border: '1px solid #e7e5e4',
                                borderRadius: '12px',
                                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                            }}
                            formatter={(value) => [`K ${value}`, '']}
                        />
                        <Bar dataKey="revenue" fill="#14b8a6" radius={[6, 6, 0, 0]} />
                        <Bar dataKey="expenses" fill="#fb923c" radius={[6, 6, 0, 0]} />
                    </BarChart>
                </ResponsiveContainer>
            </div>
        </Card>
    );
};

// Recent Transactions Component - Using Card from UI library
const RecentTransactions = ({ transactions = [] }) => {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
        }).format(amount || 0);
    };

    return (
        <Card variant="glass" padding="md">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-bold text-gray-900">Recent Transactions</h3>
                <Link href="/sales" className="text-sm font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1 group">
                    View all <ArrowRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
                </Link>
            </div>
            <div className="space-y-2">
                {transactions.length === 0 ? (
                    <EmptyState
                        icon={ShoppingCart}
                        title="No transactions yet"
                        description="Start by recording your first sale"
                    />
                ) : (
                    transactions.slice(0, 5).map((transaction, index) => (
                        <Link
                            key={transaction.id || index}
                            href={`/sales/${transaction.id}`}
                            className="flex items-center justify-between p-3 rounded-xl hover:bg-teal-50/50 transition-colors group"
                        >
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center">
                                    <Receipt className="w-5 h-5 text-teal-600" />
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-gray-900 group-hover:text-teal-600 transition-colors">
                                        {transaction.customer_name || 'Walk-in Customer'}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {transaction.sale_number || `#${index + 1}`}
                                    </p>
                                </div>
                            </div>
                            <div className="text-right">
                                <p className="text-sm font-bold text-gray-900">
                                    {formatCurrency(transaction.total_amount)}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {transaction.created_at ? new Date(transaction.created_at).toLocaleDateString() : 'Today'}
                                </p>
                            </div>
                        </Link>
                    ))
                )}
            </div>
        </Card>
    );
};

// Upcoming Deadlines Component - Using Card from UI library
const UpcomingDeadlines = ({ invoices = [] }) => {
    const overdueInvoices = invoices.filter(inv => inv.status === 'overdue');
    const pendingInvoices = invoices.filter(inv => inv.status !== 'overdue' && inv.status !== 'paid');

    return (
        <Card variant="glass" padding="md">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-bold text-gray-900">Upcoming Deadlines</h3>
                <Badge variant="neutral" size="xs">This Week</Badge>
            </div>
            <div className="space-y-2">
                {overdueInvoices.length === 0 && pendingInvoices.length === 0 ? (
                    <div className="text-center py-6">
                        <div className="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center mx-auto mb-3">
                            <CheckCircle className="w-6 h-6 text-teal-500" />
                        </div>
                        <p className="text-sm font-medium text-gray-600">All caught up!</p>
                        <p className="text-xs text-gray-400 mt-1">No pending deadlines</p>
                    </div>
                ) : (
                    <>
                        {overdueInvoices.slice(0, 2).map((invoice, index) => (
                            <Link
                                key={invoice.id || index}
                                href={`/invoices/${invoice.id}`}
                                className="flex items-center justify-between p-3 rounded-xl bg-red-50 hover:bg-red-100 transition-colors group"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="w-2 h-2 rounded-full bg-red-500 animate-pulse" />
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900">
                                            {invoice.invoice_number || 'Invoice'}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {invoice.customer_name || 'Customer'}
                                        </p>
                                    </div>
                                </div>
                                <Badge variant="negative" size="xs">Overdue</Badge>
                            </Link>
                        ))}
                        {pendingInvoices.slice(0, 3).map((invoice, index) => (
                            <Link
                                key={invoice.id || index}
                                href={`/invoices/${invoice.id}`}
                                className="flex items-center justify-between p-3 rounded-xl hover:bg-teal-50/50 transition-colors group"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="w-2 h-2 rounded-full bg-amber-500" />
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900">
                                            {invoice.invoice_number || 'Invoice'}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {invoice.customer_name || 'Customer'}
                                        </p>
                                    </div>
                                </div>
                                <Badge variant="warning" size="xs">
                                    Due {invoice.due_date ? new Date(invoice.due_date).toLocaleDateString() : 'Soon'}
                                </Badge>
                            </Link>
                        ))}
                    </>
                )}
            </div>
            <Link
                href="/invoices?status=pending"
                className="mt-4 flex items-center justify-center gap-1 text-sm font-semibold text-teal-600 hover:text-teal-700 group"
            >
                View All Tasks <ArrowRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            </Link>
        </Card>
    );
};

// Main Dashboard Component
export default function Dashboard({ 
    stats = {}, 
    recentSales = [], 
    cashFlowData = [], 
    pendingInvoices = [],
    gamification = {},
    period = 'month',
}) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const organizationName = user?.organization?.name || 'Your Business';

    // Time period state - sync with server
    const [timePeriod, setTimePeriod] = useState(period);

    const handlePeriodChange = (newPeriod) => {
        setTimePeriod(newPeriod);
        // Navigate with new period filter
        router.get('/dashboard', { period: newPeriod }, { 
            preserveState: true, 
            preserveScroll: true,
        });
    };

    // Merge gamification stats into user object for XPCard
    const userWithGamification = {
        ...user,
        ...gamification,
    };

    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    // Get period label for subtitle
    const getPeriodLabel = () => {
        switch (timePeriod) {
            case 'week': return 'This week';
            case 'month': return 'This month';
            case 'year': return 'This year';
            default: return 'This month';
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            {/* XP Notification Component */}
            <XPNotification userId={user?.id} />

            <div className="max-w-[1600px] mx-auto px-6 py-8">
                {/* Header */}
                <div className="flex items-start justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">
                            Hey, {user?.name?.split(' ')[0] || 'there'}! 👋
                        </h1>
                        <p className="text-gray-600 mt-1">
                            Here's what's happening with {organizationName} today
                        </p>
                    </div>
                    <TimePeriodSelector selected={timePeriod} onChange={handlePeriodChange} />
                </div>

                {/* Main Grid */}
                <div className="grid grid-cols-12 gap-6">
                    {/* Left Column - 8 cols */}
                    <div className="col-span-12 lg:col-span-8 space-y-6">
                        {/* Financial Stats Row */}
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <StatCard
                                title="Revenue"
                                value={formatCurrency(stats.revenue || 0)}
                                change={stats.revenueChange}
                                subtitle={getPeriodLabel()}
                                icon={DollarSign}
                                variant="gradient-positive"
                            />
                            <StatCard
                                title="Expenses"
                                value={formatCurrency(stats.expenses || 0)}
                                change={stats.expenseChange}
                                changeType={stats.expenseChange > 0 ? 'negative' : 'positive'}
                                subtitle={getPeriodLabel()}
                                icon={Receipt}
                            />
                            <StatCard
                                title="Profit"
                                value={formatCurrency(stats.profit || 0)}
                                change={stats.profitChange}
                                subtitle={`${stats.profitMargin || 0}% margin`}
                                icon={TrendingUp}
                                variant={stats.profit >= 0 ? 'gradient-positive' : 'gradient-negative'}
                            />
                            <StatCard
                                title="Outstanding"
                                value={formatCurrency(stats.outstanding || 0)}
                                subtitle={`${stats.pendingInvoicesCount || 0} invoices`}
                                icon={FileText}
                            />
                        </div>

                        {/* Cash Flow Chart */}
                        <CashFlowChart data={cashFlowData} period={timePeriod} />

                        {/* Recent Transactions */}
                        <RecentTransactions transactions={recentSales} />
                    </div>

                    {/* Right Column - 4 cols */}
                    <div className="col-span-12 lg:col-span-4 space-y-6">
                        {/* Gamification XP Card */}
                        <XPCard user={userWithGamification} />

                        {/* Upcoming Deadlines */}
                        <UpcomingDeadlines invoices={pendingInvoices} />

                        {/* Quick Actions */}
                        <QuickActions />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
