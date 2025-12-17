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
            href: '/zambian-hr/funeral-grants',
        },
        {
            title: 'Pending Gratuity Calculations',
            value: stats.pending_gratuity_calculations || 0,
            icon: Calculator,
            color: 'text-blue-600',
            bgColor: 'bg-blue-50',
            href: '/zambian-hr/gratuity',
        },
        {
            title: 'Active Grievances',
            value: stats.active_grievances || 0,
            icon: AlertTriangle,
            color: 'text-orange-600',
            bgColor: 'bg-orange-50',
            href: '/zambian-hr/grievances',
        },
        {
            title: 'Pending Terminations',
            value: stats.pending_terminations || 0,
            icon: UserX,
            color: 'text-purple-600',
            bgColor: 'bg-purple-50',
            href: '/zambian-hr/terminations',
        },
        {
            title: 'Contracts Expiring Soon',
            value: stats.contracts_expiring_soon || 0,
            icon: FileText,
            color: 'text-yellow-600',
            bgColor: 'bg-yellow-50',
            href: '/zambian-hr/contract-renewals',
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
                    <p className="text-gray-500 mt-1">Zambian labor law compliant HR management system</p>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    {statCards.map((stat, index) => (
                        <Card key={index} className="p-6 hover:shadow-lg transition-shadow cursor-pointer" onClick={() => window.location.href = stat.href}>
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
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div className="p-4 border border-gray-200 rounded-lg">
                            <h3 className="font-medium text-gray-900 mb-2">🇿🇲 Funeral Grants</h3>
                            <p className="text-sm text-gray-500">Manage funeral assistance for employees and beneficiaries</p>
                        </div>
                        <div className="p-4 border border-gray-200 rounded-lg">
                            <h3 className="font-medium text-gray-900 mb-2">💰 Gratuity (25%)</h3>
                            <p className="text-sm text-gray-500">Calculate and process end-of-service gratuity payments</p>
                        </div>
                        <div className="p-4 border border-gray-200 rounded-lg">
                            <h3 className="font-medium text-gray-900 mb-2">📅 Mother's Day Leave</h3>
                            <p className="text-sm text-gray-500">1 day per month for female employees (Zambian law)</p>
                        </div>
                        <div className="p-4 border border-gray-200 rounded-lg">
                            <h3 className="font-medium text-gray-900 mb-2">👨‍👩‍👧 Family Responsibility Leave</h3>
                            <p className="text-sm text-gray-500">7 days per year to care for sick dependents</p>
                        </div>
                        <div className="p-4 border border-gray-200 rounded-lg">
                            <h3 className="font-medium text-gray-900 mb-2">⚖️ Grievance Management</h3>
                            <p className="text-sm text-gray-500">File, investigate, and resolve employee grievances</p>
                        </div>
                        <div className="p-4 border border-gray-200 rounded-lg">
                            <h3 className="font-medium text-gray-900 mb-2">📋 Contract Renewals</h3>
                            <p className="text-sm text-gray-500">Manage fixed-term contract renewals and expiry</p>
                        </div>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

