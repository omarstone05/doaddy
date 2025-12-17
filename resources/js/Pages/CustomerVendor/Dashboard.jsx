import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import MetricCard from './Components/MetricCard';
import IncomeTimelineChart from './Components/IncomeTimelineChart';
import LiabilitiesTable from './Components/LiabilitiesTable';
import { Users, DollarSign, FileText, Briefcase } from 'lucide-react';

export default function Dashboard({ auth }) {
    // Mock Data
    const metrics = [
        { title: "Expected Income (Week)", value: "ZMW 45,200", trend: "up", trendValue: "12%", icon: DollarSign, color: "teal" },
        { title: "Outstanding Liabilities", value: "ZMW 12,500", trend: "down", trendValue: "5%", icon: FileText, color: "orange" },
        { title: "Active Customers", value: "124", trend: "up", trendValue: "8", icon: Users, color: "blue" },
        { title: "Pending Quotations", value: "ZMW 85,000", trend: "up", trendValue: "3", icon: Briefcase, color: "purple" },
    ];

    const bills = [
        { id: 1, vendor: "Zesco Ltd", due_date: "2025-12-10", amount: "ZMW 1,500", status: "Pending" },
        { id: 2, vendor: "Lusaka Water", due_date: "2025-12-05", amount: "ZMW 450", status: "Overdue" },
        { id: 3, vendor: "Office Supplies Co", due_date: "2025-12-15", amount: "ZMW 2,300", status: "Pending" },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Customer & Vendor Management</h2>}
        >
            <Head title="Customer & Vendor Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Hero Section */}
                    <div className="mb-8 bg-gradient-to-r from-teal-600 to-teal-800 rounded-2xl p-8 text-white shadow-lg">
                        <h1 className="text-3xl font-bold mb-2">Welcome back, {auth.user.name}</h1>
                        <p className="text-teal-100">Here's what's happening with your customers and vendors today.</p>
                    </div>

                    {/* Metrics Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        {metrics.map((metric, index) => (
                            <MetricCard key={index} {...metric} />
                        ))}
                    </div>

                    {/* Charts & Tables */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div className="lg:col-span-2">
                            <IncomeTimelineChart />
                        </div>
                        <div className="lg:col-span-1">
                            <LiabilitiesTable bills={bills} />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
