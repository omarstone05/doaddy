import { Head, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { TrendingUp, DollarSign, Receipt, BarChart3 } from 'lucide-react';

export default function ReportsIndex() {
    const quickReports = [
        {
            title: 'Sales Report',
            description: 'View sales performance and trends',
            href: '/reports/sales',
            icon: TrendingUp,
            color: 'bg-blue-500/10',
            iconColor: 'text-blue-500',
        },
        {
            title: 'Revenue Report',
            description: 'Track revenue from all sources',
            href: '/reports/revenue',
            icon: DollarSign,
            color: 'bg-green-500/10',
            iconColor: 'text-green-500',
        },
        {
            title: 'Expenses Report',
            description: 'Analyze expenses by category',
            href: '/reports/expenses',
            icon: Receipt,
            color: 'bg-red-500/10',
            iconColor: 'text-red-500',
        },
        {
            title: 'Profit & Loss',
            description: 'View profit and loss statement',
            href: '/reports/profit-loss',
            icon: BarChart3,
            color: 'bg-purple-500/10',
            iconColor: 'text-purple-500',
        },
    ];

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Reports" />
            
            <div className="mb-6">
                <h1 className="text-3xl font-bold text-gray-900">Reports</h1>
                <p className="text-gray-500 mt-1">Analytics and insights for your business</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {quickReports.map((report) => {
                    const Icon = report.icon;
                    return (
                        <Card
                            key={report.href}
                            className="hover:shadow-lg transition-shadow cursor-pointer"
                            onClick={() => router.visit(report.href)}
                        >
                            <CardContent className="pt-6">
                                <div className="flex flex-col items-center text-center">
                                    <div className={`p-4 rounded-full mb-4 ${report.color}`}>
                                        <Icon className={`h-8 w-8 ${report.iconColor}`} />
                                    </div>
                                    <h3 className="text-lg font-semibold text-gray-900 mb-2">{report.title}</h3>
                                    <p className="text-sm text-gray-600">{report.description}</p>
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </SectionLayout>
    );
}
