import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { FileText, TrendingUp, DollarSign, Receipt, BarChart3 } from 'lucide-react';

export default function ReportsIndex() {
    const reportCards = [
        {
            title: 'Sales Report',
            description: 'View sales performance and trends',
            href: '/reports/sales',
            icon: TrendingUp,
            color: 'bg-blue-500',
        },
        {
            title: 'Revenue Report',
            description: 'Track revenue from all sources',
            href: '/reports/revenue',
            icon: DollarSign,
            color: 'bg-green-500',
        },
        {
            title: 'Expenses Report',
            description: 'Analyze expenses by category',
            href: '/reports/expenses',
            icon: Receipt,
            color: 'bg-red-500',
        },
        {
            title: 'Profit & Loss',
            description: 'View profit and loss statement',
            href: '/reports/profit-loss',
            icon: BarChart3,
            color: 'bg-purple-500',
        },
    ];

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Reports" />
            <div>
                <div className="mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">Reports</h1>
                    <p className="text-gray-500 mt-1">Analytics and insights for your business</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {reportCards.map((report) => {
                        const Icon = report.icon;
                        return (
                            <Link
                                key={report.href}
                                href={report.href}
                                className="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow"
                            >
                                <div className={`${report.color} w-12 h-12 rounded-lg flex items-center justify-center mb-4`}>
                                    <Icon className="h-6 w-6 text-white" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">{report.title}</h3>
                                <p className="text-sm text-gray-500">{report.description}</p>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </SectionLayout>
    );
}

