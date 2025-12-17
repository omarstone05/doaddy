import { Head } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Users, UserPlus, TrendingUp, DollarSign, Calendar, CheckCircle, AlertCircle } from 'lucide-react';

export default function CRMDashboard({ stats, recentActivities, upcomingTasks, topOpportunities }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    return (
        <SectionLayout sectionName="CRM">
            <Head title="CRM Dashboard" />
            <div>
                <div className="mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">CRM Dashboard</h1>
                    <p className="text-gray-500 mt-1">Sales pipeline and customer management overview</p>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Total Leads</p>
                                <p className="text-3xl font-bold text-gray-900 mt-1">{stats?.total_leads || 0}</p>
                                <p className="text-xs text-gray-500 mt-1">{stats?.new_leads || 0} new</p>
                            </div>
                            <UserPlus className="h-8 w-8 text-teal-500" />
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Contacts</p>
                                <p className="text-3xl font-bold text-gray-900 mt-1">{stats?.total_contacts || 0}</p>
                            </div>
                            <Users className="h-8 w-8 text-blue-500" />
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Pipeline Value</p>
                                <p className="text-3xl font-bold text-gray-900 mt-1">{formatCurrency(stats?.pipeline_value)}</p>
                                <p className="text-xs text-gray-500 mt-1">Weighted: {formatCurrency(stats?.weighted_pipeline)}</p>
                            </div>
                            <TrendingUp className="h-8 w-8 text-green-500" />
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Won This Month</p>
                                <p className="text-3xl font-bold text-gray-900 mt-1">{formatCurrency(stats?.won_this_month)}</p>
                            </div>
                            <DollarSign className="h-8 w-8 text-yellow-500" />
                        </div>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Top Opportunities */}
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Top Opportunities</h2>
                        {topOpportunities && topOpportunities.length > 0 ? (
                            <div className="space-y-4">
                                {topOpportunities.map((opp) => (
                                    <div key={opp.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p className="font-medium text-gray-900">{opp.name}</p>
                                            <p className="text-sm text-gray-500">{opp.contact?.full_name || 'N/A'}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-semibold text-gray-900">{formatCurrency(opp.amount)}</p>
                                            <p className="text-xs text-gray-500">{opp.probability}%</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500 text-center py-8">No opportunities yet</p>
                        )}
                    </Card>

                    {/* Upcoming Tasks */}
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Upcoming Tasks</h2>
                        {upcomingTasks && upcomingTasks.length > 0 ? (
                            <div className="space-y-3">
                                {upcomingTasks.map((task) => (
                                    <div key={task.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <CheckCircle className="h-5 w-5 text-teal-500" />
                                        <div className="flex-1">
                                            <p className="font-medium text-gray-900">{task.subject}</p>
                                            <p className="text-sm text-gray-500">
                                                Due: {new Date(task.due_date).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500 text-center py-8">No upcoming tasks</p>
                        )}
                    </Card>
                </div>
            </div>
        </SectionLayout>
    );
}


