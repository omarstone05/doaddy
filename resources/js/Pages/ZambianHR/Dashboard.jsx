import { Head } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Heart, Calculator, AlertTriangle, UserX, FileText, Shield } from 'lucide-react';

export default function ZambianHRDashboard({ stats }) {
    const statCards = [
        {
            title: 'Pending Funeral Grants',
            value: stats.pending_funeral_grants || 0,
            icon: Heart,
            color: 'text-red-600',
            bgColor: 'bg-red-50',
        },
        {
            title: 'Pending Gratuity Calculations',
            value: stats.pending_gratuity_calculations || 0,
            icon: Calculator,
            color: 'text-blue-600',
            bgColor: 'bg-blue-50',
        },
        {
            title: 'Active Grievances',
            value: stats.active_grievances || 0,
            icon: AlertTriangle,
            color: 'text-orange-600',
            bgColor: 'bg-orange-50',
        },
        {
            title: 'Pending Terminations',
            value: stats.pending_terminations || 0,
            icon: UserX,
            color: 'text-purple-600',
            bgColor: 'bg-purple-50',
        },
        {
            title: 'Contracts Expiring Soon',
            value: stats.contracts_expiring_soon || 0,
            icon: FileText,
            color: 'text-yellow-600',
            bgColor: 'bg-yellow-50',
        },
    ];

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Zambian HR Dashboard" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <div className="flex items-center gap-3 mb-2">
                        <Shield className="h-6 w-6 text-teal-600" />
                        <h1 className="text-3xl font-bold text-gray-900">Zambian HR Compliance</h1>
                    </div>
                    <p className="text-gray-500 mt-1">Manage your workforce with Zambian labor law compliance</p>
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
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Zambian HR Features</h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="/zambian-hr/funeral-grants" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <Heart className="h-5 w-5 text-red-600 mb-2" />
                            <p className="font-medium text-gray-900">Funeral Grants</p>
                            <p className="text-sm text-gray-500">Manage funeral assistance</p>
                        </a>
                        <a href="/zambian-hr/gratuity" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <Calculator className="h-5 w-5 text-blue-600 mb-2" />
                            <p className="font-medium text-gray-900">Gratuity</p>
                            <p className="text-sm text-gray-500">Calculate gratuity payments</p>
                        </a>
                        <a href="/zambian-hr/grievances" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <AlertTriangle className="h-5 w-5 text-orange-600 mb-2" />
                            <p className="font-medium text-gray-900">Grievances</p>
                            <p className="text-sm text-gray-500">Manage employee grievances</p>
                        </a>
                        <a href="/zambian-hr/terminations" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <UserX className="h-5 w-5 text-purple-600 mb-2" />
                            <p className="font-medium text-gray-900">Terminations</p>
                            <p className="text-sm text-gray-500">Process terminations</p>
                        </a>
                        <a href="/zambian-hr/contract-renewals" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <FileText className="h-5 w-5 text-yellow-600 mb-2" />
                            <p className="font-medium text-gray-900">Contract Renewals</p>
                            <p className="text-sm text-gray-500">Manage contract renewals</p>
                        </a>
                        <a href="/zambian-hr/conflict-of-interest" className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <Shield className="h-5 w-5 text-teal-600 mb-2" />
                            <p className="font-medium text-gray-900">Conflict of Interest</p>
                            <p className="text-sm text-gray-500">Manage declarations</p>
                        </a>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

