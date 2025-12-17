import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Users, Calendar, DollarSign, Briefcase, TrendingUp, Clock } from 'lucide-react';

export default function HRDashboard({ stats }) {
    const statCards = [
        {
            title: 'Total Employees',
            value: stats.total_employees || 0,
            icon: Users,
            color: 'text-blue-600',
            bgColor: 'bg-blue-50',
        },
        {
            title: 'Active Employees',
            value: stats.active_employees || 0,
            icon: Users,
            color: 'text-green-600',
            bgColor: 'bg-green-50',
        },
        {
            title: 'On Leave Today',
            value: stats.on_leave_today || 0,
            icon: Calendar,
            color: 'text-yellow-600',
            bgColor: 'bg-yellow-50',
        },
        {
            title: 'Pending Leave Requests',
            value: stats.pending_leave_requests || 0,
            icon: Calendar,
            color: 'text-orange-600',
            bgColor: 'bg-orange-50',
        },
        {
            title: 'Upcoming Reviews',
            value: stats.upcoming_reviews || 0,
            icon: Briefcase,
            color: 'text-purple-600',
            bgColor: 'bg-purple-50',
        },
        {
            title: 'Open Positions',
            value: stats.open_positions || 0,
            icon: Briefcase,
            color: 'text-teal-600',
            bgColor: 'bg-teal-50',
        },
    ];

    return (
        <SectionLayout sectionName="HR">
            <Head title="HR Dashboard" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <div className="flex items-center gap-3 mb-2">
                        <Users className="h-6 w-6 text-teal-600" />
                        <h1 className="text-3xl font-bold text-gray-900">HR Dashboard</h1>
                    </div>
                    <p className="text-gray-500 mt-1">Manage your workforce and employee lifecycle</p>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    {statCards.map((stat, index) => (
                        <Card key={index} className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stat.value}</p>
                                </div>
                                <div className={`${stat.bgColor} p-3 rounded-lg`}>
                                    <stat.icon className={`h-6 w-6 ${stat.color}`} />
                                </div>
                            </div>
                        </Card>
                    ))}
                </div>

                {/* Quick Actions */}
                <Card className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Link href="/hr/employees/create" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <Users className="h-5 w-5 text-teal-600 mb-2" />
                            <p className="font-medium text-gray-900">Add Employee</p>
                            <p className="text-sm text-gray-500">Create a new employee record</p>
                        </Link>
                        <Link href="/hr/employees" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <Users className="h-5 w-5 text-teal-600 mb-2" />
                            <p className="font-medium text-gray-900">View Employees</p>
                            <p className="text-sm text-gray-500">Manage employee records</p>
                        </Link>
                        <Link href="/payroll/runs" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <Calendar className="h-5 w-5 text-teal-600 mb-2" />
                            <p className="font-medium text-gray-900">Process Payroll</p>
                            <p className="text-sm text-gray-500">Run monthly payroll</p>
                        </Link>
                        <Link href="/leave/requests" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <Calendar className="h-5 w-5 text-teal-600 mb-2" />
                            <p className="font-medium text-gray-900">Leave Requests</p>
                            <p className="text-sm text-gray-500">Manage leave applications</p>
                        </Link>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

