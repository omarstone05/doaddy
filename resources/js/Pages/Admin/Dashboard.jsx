import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { 
    Building2, 
    Users, 
    Ticket,
    TrendingUp,
    AlertCircle,
    CheckCircle2
} from 'lucide-react';
import {
    LineChart,
    Line,
    AreaChart,
    Area,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';

export default function Dashboard({ stats, charts, system_health }) {
    const statCards = [
        {
            title: 'Total Organizations',
            value: stats?.organizations?.total || 0,
            change: stats?.organizations?.new_this_month || 0,
            changeLabel: 'New this month',
            icon: Building2,
            color: 'text-blue-600',
            bgColor: 'bg-blue-50',
        },
        {
            title: 'Total Users',
            value: stats?.users?.total || 0,
            change: stats?.users?.new_this_month || 0,
            changeLabel: 'New this month',
            icon: Users,
            color: 'text-purple-600',
            bgColor: 'bg-purple-50',
        },
        {
            title: 'Open Tickets',
            value: stats?.support?.open_tickets || 0,
            change: stats?.support?.urgent_tickets || 0,
            changeLabel: 'Urgent',
            icon: Ticket,
            color: 'text-orange-600',
            bgColor: 'bg-orange-50',
        },
        {
            title: 'MRR',
            value: `$${((stats?.revenue?.mrr || 0)).toLocaleString()}`,
            change: `$${((stats?.revenue?.arr || 0)).toLocaleString()}`,
            changeLabel: 'ARR',
            icon: TrendingUp,
            color: 'text-teal-600',
            bgColor: 'bg-teal-50',
        },
    ];

    const healthStatus = (status) => {
        switch (status) {
            case 'healthy':
                return { icon: CheckCircle2, color: 'text-green-600' };
            case 'warning':
                return { icon: AlertCircle, color: 'text-yellow-600' };
            case 'error':
                return { icon: AlertCircle, color: 'text-red-600' };
            default:
                return { icon: CheckCircle2, color: 'text-gray-600' };
        }
    };

    return (
        <AdminLayout title="Admin Dashboard">
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        System overview and health monitoring
                    </p>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {statCards.map((stat) => {
                        const Icon = stat.icon;
                        return (
                            <Card key={stat.title} className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">
                                            {stat.title}
                                        </p>
                                        <p className="mt-2 text-3xl font-bold text-gray-900">
                                            {stat.value}
                                        </p>
                                        <p className="mt-1 text-sm text-gray-500">
                                            {stat.changeLabel}: {stat.change}
                                        </p>
                                    </div>
                                    <div className={`p-3 rounded-lg ${stat.bgColor}`}>
                                        <Icon className={`w-6 h-6 ${stat.color}`} />
                                    </div>
                                </div>
                            </Card>
                        );
                    })}
                </div>

                {/* Charts Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Organization Growth */}
                    <Card className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            Organization Growth
                        </h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <AreaChart data={charts?.organizations?.values?.map((value, index) => ({
                                date: charts?.organizations?.labels?.[index] || '',
                                count: value || 0,
                            })) || []}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Area
                                    type="monotone"
                                    dataKey="count"
                                    stroke="#14b8a6"
                                    fill="#14b8a6"
                                    fillOpacity={0.2}
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </Card>

                    {/* Ticket Volume */}
                    <Card className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            Ticket Volume
                        </h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={charts.tickets?.created?.map((created, index) => ({
                                date: charts.tickets?.labels?.[index] || '',
                                created: created || 0,
                                resolved: charts.tickets?.resolved?.[index] || 0,
                            })) || []}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Legend />
                                <Bar dataKey="created" fill="#f97316" />
                                <Bar dataKey="resolved" fill="#10b981" />
                            </BarChart>
                        </ResponsiveContainer>
                    </Card>
                </div>

                {/* User Activity Chart */}
                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                        User Activity
                    </h3>
                    <ResponsiveContainer width="100%" height={300}>
                        <LineChart data={charts.users?.values?.map((value, index) => ({
                            date: charts.users?.labels?.[index] || '',
                            count: value || 0,
                        })) || []}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="date" />
                            <YAxis />
                            <Tooltip />
                            <Line
                                type="monotone"
                                dataKey="count"
                                stroke="#8b5cf6"
                                strokeWidth={2}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </Card>

                {/* System Health */}
                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                        System Health
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        {Object.entries(system_health).map(([key, health]) => {
                            const { icon: Icon, color } = healthStatus(health.status);
                            return (
                                <div
                                    key={key}
                                    className="flex items-center space-x-3 p-4 bg-gray-50 rounded-lg"
                                >
                                    <Icon className={`w-5 h-5 ${color}`} />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900 capitalize">
                                            {key}
                                        </p>
                                        <p className="text-xs text-gray-500">{health.message}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </Card>

                {/* Support & Platform Stats */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">
                                    Avg Response Time
                                </p>
                                <p className="mt-2 text-2xl font-bold text-gray-900">
                                    {stats.support?.avg_response_time
                                        ? `${stats.support.avg_response_time}h`
                                        : 'N/A'}
                                </p>
                            </div>
                            <Ticket className="w-8 h-8 text-orange-500" />
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">
                                    Unassigned Tickets
                                </p>
                                <p className="mt-2 text-2xl font-bold text-gray-900">
                                    {stats.support?.unassigned || 0}
                                </p>
                            </div>
                            <AlertCircle className="w-8 h-8 text-red-500" />
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">
                                    Active Users Today
                                </p>
                                <p className="mt-2 text-2xl font-bold text-gray-900">
                                    {stats.users?.active_today || 0}
                                </p>
                            </div>
                            <Users className="w-8 h-8 text-blue-500" />
                        </div>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}

