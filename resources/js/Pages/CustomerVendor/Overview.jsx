import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    TrendingUp, 
    TrendingDown, 
    AlertCircle, 
    Users, 
    FileText, 
    DollarSign,
    Calendar,
    Building
} from 'lucide-react';

export default function Overview({ 
    projectedIncome, 
    upcomingLiabilities, 
    customerMetrics, 
    quotationMetrics,
    customerPersonas,
    incomeTimeline,
    upcomingBills 
}) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Customer & Vendor Management" />

            <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900">
                            Customer & Vendor Management
                        </h1>
                        <p className="text-gray-600 mt-2">
                            Manage relationships, track income, and monitor liabilities
                        </p>
                    </div>

                    {/* Hero Insight Card */}
                    <div className="mb-8">
                        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-teal-700 p-8 text-white shadow-xl">
                            {/* Decorative elements */}
                            <div className="absolute top-0 right-0 -mt-4 -mr-4 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>
                            <div className="absolute bottom-0 left-0 -mb-8 -ml-8 h-40 w-40 rounded-full bg-teal-400/20 blur-3xl"></div>
                            
                            <div className="relative">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-teal-100 text-sm font-medium mb-2">
                                            Financial Health Snapshot
                                        </p>
                                        <h2 className="text-4xl font-bold mb-2">
                                            {formatCurrency(projectedIncome.total)}
                                        </h2>
                                        <p className="text-teal-100 text-lg">
                                            Expected from {projectedIncome.invoice_count} pending invoices
                                        </p>
                                        {projectedIncome.overdue > 0 && (
                                            <div className="mt-4 inline-flex items-center gap-2 bg-red-500/20 px-3 py-1.5 rounded-lg">
                                                <AlertCircle className="w-4 h-4" />
                                                <span className="text-sm font-medium">
                                                    {formatCurrency(projectedIncome.overdue)} overdue
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex flex-col gap-3">
                                        <Link
                                            href="/customers/invoices"
                                            className="px-6 py-3 bg-white text-teal-600 rounded-lg font-semibold hover:bg-teal-50 transition-colors shadow-lg"
                                        >
                                            View Invoices
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Key Metrics Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        {/* Projected Income */}
                        <MetricCard
                            title="This Week"
                            value={formatCurrency(projectedIncome.this_week)}
                            icon={Calendar}
                            trend={projectedIncome.this_week > 0 ? 'up' : 'neutral'}
                            color="teal"
                        />

                        {/* Outstanding Liabilities */}
                        <MetricCard
                            title="Total Liabilities"
                            value={formatCurrency(upcomingLiabilities.total)}
                            subtitle={`${upcomingLiabilities.vendor_count} vendors`}
                            icon={AlertCircle}
                            trend={upcomingLiabilities.overdue > 0 ? 'down' : 'neutral'}
                            color="red"
                        />

                        {/* Active Customers */}
                        <MetricCard
                            title="Active Customers"
                            value={customerMetrics.active}
                            subtitle={`+${customerMetrics.new_this_month} this month`}
                            icon={Users}
                            trend="up"
                            color="blue"
                        />

                        {/* Pending Quotations */}
                        <MetricCard
                            title="Pending Quotes"
                            value={quotationMetrics.pending}
                            subtitle={`${formatCurrency(quotationMetrics.total_value)} potential`}
                            icon={FileText}
                            trend="neutral"
                            color="purple"
                        />
                    </div>

                    {/* Two Column Layout */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        {/* Projected Income Timeline */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Projected Income Timeline
                                </h3>
                                <Link
                                    href="/customers/invoices"
                                    className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                                >
                                    View All →
                                </Link>
                            </div>
                            <IncomeTimelineChart data={incomeTimeline} />
                        </div>

                        {/* Upcoming Liabilities */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Upcoming Liabilities
                                </h3>
                                <Link
                                    href="/vendors/bills"
                                    className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                                >
                                    View All →
                                </Link>
                            </div>
                            <LiabilitiesTable bills={upcomingBills} formatCurrency={formatCurrency} />
                        </div>
                    </div>

                    {/* Customer Personas */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                        <div className="flex items-center justify-between mb-6">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Customer Personas
                            </h3>
                            <Link
                                href="/customers/personas"
                                className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                            >
                                Manage →
                            </Link>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            {customerPersonas.map((persona) => (
                                <PersonaCard key={persona.id} persona={persona} formatCurrency={formatCurrency} />
                            ))}
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-6">
                            Quick Actions
                        </h3>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <QuickAction
                                icon={Users}
                                label="Add Customer"
                                href="/customers/create"
                                color="teal"
                            />
                            <QuickAction
                                icon={FileText}
                                label="Create Quotation"
                                href="/quotations/create"
                                color="purple"
                            />
                            <QuickAction
                                icon={DollarSign}
                                label="Record Bill"
                                href="/bills/create"
                                color="red"
                            />
                            <QuickAction
                                icon={Building}
                                label="Add Vendor"
                                href="/vendors/create"
                                color="blue"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Metric Card Component
function MetricCard({ title, value, subtitle, icon: Icon, trend, color }) {
    const colorClasses = {
        teal: 'bg-teal-50 text-teal-600',
        red: 'bg-red-50 text-red-600',
        blue: 'bg-blue-50 text-blue-600',
        purple: 'bg-purple-50 text-purple-600',
    };

    const trendIcons = {
        up: <TrendingUp className="w-4 h-4 text-green-500" />,
        down: <TrendingDown className="w-4 h-4 text-red-500" />,
        neutral: null,
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div className="flex items-start justify-between mb-4">
                <div className={`p-3 rounded-lg ${colorClasses[color]}`}>
                    <Icon className="w-6 h-6" />
                </div>
                {trendIcons[trend]}
            </div>
            <h3 className="text-sm font-medium text-gray-600 mb-1">{title}</h3>
            <p className="text-2xl font-bold text-gray-900 mb-1">{value}</p>
            {subtitle && <p className="text-sm text-gray-500">{subtitle}</p>}
        </div>
    );
}

// Income Timeline Chart Component
function IncomeTimelineChart({ data }) {
    const maxAmount = Math.max(...data.map(d => d.amount));

    return (
        <div className="space-y-3">
            {data.slice(0, 8).map((week, index) => {
                const widthPercentage = maxAmount > 0 ? (week.amount / maxAmount) * 100 : 0;
                
                return (
                    <div key={index} className="flex items-center gap-4">
                        <div className="w-16 text-sm text-gray-600 font-medium">
                            {week.week}
                        </div>
                        <div className="flex-1">
                            <div className="relative h-8 bg-gray-100 rounded-lg overflow-hidden">
                                <div
                                    className="absolute inset-y-0 left-0 bg-gradient-to-r from-teal-500 to-teal-600 rounded-lg flex items-center justify-end pr-3"
                                    style={{ width: `${Math.max(widthPercentage, 5)}%` }}
                                >
                                    {week.amount > 0 && (
                                        <span className="text-xs font-semibold text-white">
                                            ${(week.amount / 1000).toFixed(0)}k
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="w-12 text-xs text-gray-500 text-right">
                            {week.invoice_count} inv
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

// Liabilities Table Component
function LiabilitiesTable({ bills, formatCurrency }) {
    if (bills.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                <p>No upcoming liabilities</p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {bills.map((bill) => (
                <Link
                    key={bill.id}
                    href={`/bills/${bill.id}`}
                    className="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors group"
                >
                    <div className="flex-1">
                        <div className="flex items-center gap-2">
                            <p className="font-medium text-gray-900 group-hover:text-teal-600 transition-colors">
                                {bill.vendor_name}
                            </p>
                            {bill.is_overdue && (
                                <span className="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded">
                                    Overdue
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-gray-500 mt-1">
                            Due: {bill.due_date_formatted}
                        </p>
                    </div>
                    <div className="text-right">
                        <p className="font-semibold text-gray-900">
                            {formatCurrency(bill.amount_due)}
                        </p>
                        <p className="text-xs text-gray-500">
                            {bill.bill_number}
                        </p>
                    </div>
                </Link>
            ))}
        </div>
    );
}

// Persona Card Component
function PersonaCard({ persona, formatCurrency }) {
    return (
        <div 
            className="p-5 rounded-xl border-2 hover:shadow-lg transition-all cursor-pointer group"
            style={{ borderColor: persona.color }}
        >
            <div 
                className="w-12 h-12 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"
                style={{ backgroundColor: `${persona.color}20` }}
            >
                {persona.icon ? (
                    <span className="text-2xl">{persona.icon}</span>
                ) : (
                    <Building className="w-6 h-6" style={{ color: persona.color }} />
                )}
            </div>
            <h4 className="font-semibold text-gray-900 mb-1">{persona.name}</h4>
            <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                {persona.description || `${persona.industry} - ${persona.size}`}
            </p>
            <div className="flex items-center justify-between text-sm">
                <span className="text-gray-500">{persona.customer_count} customers</span>
                <span className="font-medium" style={{ color: persona.color }}>
                    {formatCurrency(persona.total_revenue)}
                </span>
            </div>
        </div>
    );
}

// Quick Action Component
function QuickAction({ icon: Icon, label, href, color }) {
    const colorClasses = {
        teal: 'hover:bg-teal-50 hover:text-teal-600 hover:border-teal-200',
        red: 'hover:bg-red-50 hover:text-red-600 hover:border-red-200',
        blue: 'hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200',
        purple: 'hover:bg-purple-50 hover:text-purple-600 hover:border-purple-200',
    };

    return (
        <Link
            href={href}
            className={`flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-xl transition-all ${colorClasses[color]}`}
        >
            <Icon className="w-8 h-8 mb-3" />
            <span className="text-sm font-medium text-center">{label}</span>
        </Link>
    );
}
