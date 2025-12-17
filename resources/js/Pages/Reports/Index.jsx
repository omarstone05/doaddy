import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { TrendingUp, DollarSign, Receipt, BarChart3, ArrowRight } from 'lucide-react';

export default function ReportsIndex() {
    const quickReports = [
        {
            title: 'Sales Report',
            description: 'View sales performance and trends',
            href: '/reports/sales',
            icon: TrendingUp,
            gradient: 'from-teal-500 to-teal-600',
            bgColor: 'bg-teal-50',
        },
        {
            title: 'Revenue Report',
            description: 'Track revenue from all sources',
            href: '/reports/revenue',
            icon: DollarSign,
            gradient: 'from-emerald-500 to-emerald-600',
            bgColor: 'bg-emerald-50',
        },
        {
            title: 'Expenses Report',
            description: 'Analyze expenses by category',
            href: '/reports/expenses',
            icon: Receipt,
            gradient: 'from-orange-500 to-red-500',
            bgColor: 'bg-orange-50',
        },
        {
            title: 'Profit & Loss',
            description: 'View profit and loss statement',
            href: '/reports/profit-loss',
            icon: BarChart3,
            gradient: 'from-purple-500 to-indigo-600',
            bgColor: 'bg-purple-50',
        },
        {
            title: 'Liabilities Report',
            description: 'Track bills and outstanding liabilities',
            href: '/reports/liabilities',
            icon: Receipt,
            gradient: 'from-red-500 to-red-600',
            bgColor: 'bg-red-50',
        },
        {
            title: 'Projected Income',
            description: 'View projected income from invoices and quotes',
            href: '/reports/projected-income',
            icon: DollarSign,
            gradient: 'from-green-500 to-green-600',
            bgColor: 'bg-green-50',
        },
    ];

    return (
        <SectionLayout sectionName="Reports">
            <Head title="Reports" />

            <div className="mb-8">
                <h1 className="text-3xl font-black text-gray-900 tracking-tight">Reports</h1>
                <p className="text-gray-500 mt-1">Analytics and insights for your business</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {quickReports.map((report) => {
                    const Icon = report.icon;
                    return (
                        <Link key={report.href} href={report.href} className="block group">
                            <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6 hover:shadow-xl hover:border-teal-200 transition-all duration-300 relative overflow-hidden h-full">
                                {/* Background gradient decoration */}
                                <div className={`absolute top-0 right-0 w-32 h-32 ${report.bgColor} rounded-full blur-3xl opacity-50 -translate-y-1/2 translate-x-1/2 group-hover:opacity-80 transition-opacity`} />
                                
                                <div className="relative z-10">
                                    <div className={`w-14 h-14 rounded-2xl bg-gradient-to-br ${report.gradient} flex items-center justify-center mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300`}>
                                        <Icon className="h-7 w-7 text-white" strokeWidth={1.5} />
                                    </div>
                                    <h3 className="text-lg font-bold text-gray-900 mb-2 group-hover:text-teal-700 transition-colors">{report.title}</h3>
                                    <p className="text-sm text-gray-500 mb-4">{report.description}</p>
                                    <div className="flex items-center text-teal-600 text-sm font-semibold group-hover:text-teal-700">
                                        View Report
                                        <ArrowRight className="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                                    </div>
                                </div>
                            </div>
                        </Link>
                    );
                })}
            </div>
        </SectionLayout>
    );
}
