import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { 
    Card, 
    CardHeader,
    StatCard,
} from '@/Components/ui';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { 
    TrendingUp, 
    TrendingDown, 
    AlertCircle, 
    Users, 
    FileText, 
    DollarSign,
    Calendar,
    Building,
    Plus
} from 'lucide-react';

export default function PeopleIndex({ 
    projectedIncome, 
    upcomingLiabilities, 
    customerMetrics, 
    quotationMetrics,
    customerPersonas,
    incomeTimeline,
    upcomingBills,
    billTimeline,
    insights 
}) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount || 0);
    };

    const formatDate = (dateString) => {
        if (!dateString) return '';
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
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
            title: 'Create Quotation',
            description: 'Generate quote',
            icon: FileText,
            href: '/quotations/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Record Bill',
            description: 'Add bill',
            icon: DollarSign,
            href: '/bills/create',
            iconColor: 'text-teal-500',
        },
        {
            title: 'Add Vendor',
            description: 'New vendor',
            icon: Building,
            href: '/vendors/create',
            iconColor: 'text-teal-500',
        },
    ];

    return (
        <SectionLayout sectionName="People">
            <Head title="People" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="People" 
                insights={insights || []}
            />

            {/* Hero Financial Health Card */}
            {projectedIncome && (
                <Card variant="gradient-positive" padding="lg" className="mb-8">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-white/80 mb-2">
                                Financial Health Snapshot
                            </p>
                            <h2 className="text-4xl font-black text-white mb-2">
                                {formatCurrency(projectedIncome.total)}
                            </h2>
                            <p className="text-white/90 text-lg">
                                Expected from {projectedIncome.invoice_count || 0} pending invoices
                            </p>
                            {projectedIncome.overdue > 0 && (
                                <div className="mt-4 inline-flex items-center gap-2 bg-red-500/30 px-3 py-1.5 rounded-lg">
                                    <AlertCircle className="w-4 h-4" />
                                    <span className="text-sm font-medium">
                                        {formatCurrency(projectedIncome.overdue)} overdue
                                    </span>
                                </div>
                            )}
                        </div>
                        <Link
                            href="/invoices"
                            className="px-6 py-3 bg-white text-teal-600 rounded-xl font-semibold hover:bg-teal-50 transition-colors shadow-lg"
                        >
                            View Invoices
                        </Link>
                    </div>
                </Card>
            )}

            {/* Key Metrics Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {projectedIncome && (
                    <StatCard
                        title="This Week"
                        value={formatCurrency(projectedIncome.this_week)}
                        icon={Calendar}
                        variant="glass"
                    />
                )}
                {upcomingLiabilities && (
                    <StatCard
                        title="Total Liabilities"
                        value={formatCurrency(upcomingLiabilities.total)}
                        subtitle={`${upcomingLiabilities.vendor_count || 0} vendors`}
                        icon={AlertCircle}
                        variant="glass"
                    />
                )}
                {customerMetrics && (
                    <StatCard
                        title="Active Customers"
                        value={customerMetrics.active || 0}
                        subtitle={`+${customerMetrics.new_this_month || 0} this month`}
                        icon={Users}
                        variant="glass"
                    />
                )}
                {quotationMetrics && (
                    <StatCard
                        title="Pending Quotes"
                        value={quotationMetrics.pending || 0}
                        subtitle={formatCurrency(quotationMetrics.total_value || 0)}
                        icon={FileText}
                        variant="glass"
                    />
                )}
            </div>

            {/* Two Column Layout - Income and Bills Timelines */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                {/* Projected Income Timeline */}
                {incomeTimeline && incomeTimeline.length > 0 && (
                    <Card variant="glass" padding="md">
                        <CardHeader label="Projected Income Timeline" />
                        <IncomeTimelineChart data={incomeTimeline} formatCurrency={formatCurrency} />
                    </Card>
                )}

                {/* Projected Bill (Liability) Timeline */}
                {billTimeline && billTimeline.length > 0 && (
                    <Card variant="glass" padding="md">
                        <CardHeader label="Projected Bill (Liability) Timeline" />
                        <BillTimelineChart data={billTimeline} formatCurrency={formatCurrency} />
                    </Card>
                )}
            </div>

            {/* Upcoming Liabilities Table */}
            {upcomingBills && upcomingBills.length > 0 && (
                <Card variant="glass" padding="md" className="mb-8">
                    <CardHeader label="Upcoming Liabilities" />
                    <LiabilitiesTable bills={upcomingBills} formatCurrency={formatCurrency} />
                </Card>
            )}

            {/* Customer Personas */}
            {customerPersonas && customerPersonas.length > 0 && (
                <Card variant="glass" padding="md" className="mb-8">
                    <CardHeader label="Customer Personas" />
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {customerPersonas.map((persona) => (
                            <PersonaCard key={persona.id} persona={persona} formatCurrency={formatCurrency} />
                        ))}
                    </div>
                </Card>
            )}

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
        </SectionLayout>
    );
}

// Income Timeline Chart Component
function IncomeTimelineChart({ data, formatCurrency }) {
    if (!data || data.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                <p>No projected income data</p>
            </div>
        );
    }

    const maxAmount = Math.max(...data.map(d => d.amount || 0), 1);

    return (
        <div className="space-y-3">
            {data.slice(0, 8).map((week, index) => {
                const widthPercentage = maxAmount > 0 ? ((week.amount || 0) / maxAmount) * 100 : 0;
                
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
                                            {formatCurrency(week.amount)}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="w-12 text-xs text-gray-500 text-right">
                            {week.invoice_count || 0} inv
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

// Bill Timeline Chart Component
function BillTimelineChart({ data, formatCurrency }) {
    if (!data || data.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                <p>No projected bills data</p>
            </div>
        );
    }

    const maxAmount = Math.max(...data.map(d => d.amount || 0), 1);

    return (
        <div className="space-y-3">
            {data.slice(0, 8).map((week, index) => {
                const widthPercentage = maxAmount > 0 ? ((week.amount || 0) / maxAmount) * 100 : 0;
                
                return (
                    <div key={index} className="flex items-center gap-4">
                        <div className="w-16 text-sm text-gray-600 font-medium">
                            {week.week}
                        </div>
                        <div className="flex-1">
                            <div className="relative h-8 bg-gray-100 rounded-lg overflow-hidden">
                                <div
                                    className="absolute inset-y-0 left-0 bg-gradient-to-r from-red-500 to-red-600 rounded-lg flex items-center justify-end pr-3"
                                    style={{ width: `${Math.max(widthPercentage, 5)}%` }}
                                >
                                    {week.amount > 0 && (
                                        <span className="text-xs font-semibold text-white">
                                            {formatCurrency(week.amount)}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="w-12 text-xs text-gray-500 text-right">
                            {week.bill_count || 0} bills
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

// Liabilities Table Component
function LiabilitiesTable({ bills, formatCurrency }) {
    if (!bills || bills.length === 0) {
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
                                {bill.vendor_name || 'Unknown Vendor'}
                            </p>
                            {bill.is_overdue && (
                                <span className="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded">
                                    Overdue
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-gray-500 mt-1">
                            Due: {bill.due_date_formatted || formatDate(bill.due_date)}
                        </p>
                    </div>
                    <div className="text-right">
                        <p className="font-semibold text-gray-900">
                            {formatCurrency(bill.amount_due || 0)}
                        </p>
                        <p className="text-xs text-gray-500">
                            {bill.bill_number || 'N/A'}
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
            style={{ borderColor: persona.color || '#14b8a6' }}
        >
            <div 
                className="w-12 h-12 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"
                style={{ backgroundColor: `${persona.color || '#14b8a6'}20` }}
            >
                {persona.icon ? (
                    <span className="text-2xl">{persona.icon}</span>
                ) : (
                    <Building className="w-6 h-6" style={{ color: persona.color || '#14b8a6' }} />
                )}
            </div>
            <h4 className="font-semibold text-gray-900 mb-1">{persona.name}</h4>
            <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                {persona.description || `${persona.industry || ''} - ${persona.size || ''}`}
            </p>
            <div className="flex items-center justify-between text-sm">
                <span className="text-gray-500">{persona.customer_count || 0} customers</span>
                <span className="font-medium" style={{ color: persona.color || '#14b8a6' }}>
                    {formatCurrency(persona.total_revenue || 0)}
                </span>
            </div>
        </div>
    );
}
