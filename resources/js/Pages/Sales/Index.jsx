import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { Users, ShoppingCart, FileText, DollarSign, Plus, Receipt } from 'lucide-react';

export default function SalesIndex({ stats, insights }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    return (
        <SectionLayout sectionName="Sales">
            <Head title="Sales" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="Sales" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Total Customers</p>
                                <p className="text-3xl font-bold text-teal-500">{stats?.total_customers || 0}</p>
                            </div>
                            <div className="p-3 bg-teal-500/10 rounded-lg">
                                <Users className="h-6 w-6 text-teal-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Monthly Sales</p>
                                <p className="text-3xl font-bold text-green-500">{formatCurrency(stats?.monthly_sales)}</p>
                            </div>
                            <div className="p-3 bg-green-500/10 rounded-lg">
                                <ShoppingCart className="h-6 w-6 text-green-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Pending Invoices</p>
                                <p className="text-3xl font-bold text-amber-500">{stats?.pending_invoices || 0}</p>
                            </div>
                            <div className="p-3 bg-amber-500/10 rounded-lg">
                                <FileText className="h-6 w-6 text-amber-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Pending Quotes</p>
                                <p className="text-3xl font-bold text-blue-500">{stats?.pending_quotes || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-500/10 rounded-lg">
                                <FileText className="h-6 w-6 text-blue-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Quick Actions */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => router.visit('/pos')}>
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center">
                            <div className="p-4 bg-teal-500/10 rounded-full mb-4">
                                <ShoppingCart className="h-8 w-8 text-teal-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Point of Sale</h3>
                            <p className="text-sm text-gray-600">Open POS system</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => router.visit('/customers/create')}>
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center">
                            <div className="p-4 bg-teal-500/10 rounded-full mb-4">
                                <Users className="h-8 w-8 text-teal-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Add Customer</h3>
                            <p className="text-sm text-gray-600">Create a new customer</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => router.visit('/quotes/create')}>
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center">
                            <div className="p-4 bg-blue-500/10 rounded-full mb-4">
                                <FileText className="h-8 w-8 text-blue-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Create Quote</h3>
                            <p className="text-sm text-gray-600">Generate a new quote</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => router.visit('/invoices/create')}>
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center">
                            <div className="p-4 bg-amber-500/10 rounded-full mb-4">
                                <FileText className="h-8 w-8 text-amber-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Create Invoice</h3>
                            <p className="text-sm text-gray-600">Generate a new invoice</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => router.visit('/payments/create')}>
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center">
                            <div className="p-4 bg-green-500/10 rounded-full mb-4">
                                <DollarSign className="h-8 w-8 text-green-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Record Payment</h3>
                            <p className="text-sm text-gray-600">Record a payment</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Recent Sales */}
            <Card>
                <CardHeader>
                    <CardTitle>Recent Sales</CardTitle>
                </CardHeader>
                <CardContent>
                    {stats?.recent_sales && stats.recent_sales.length > 0 ? (
                        <div className="space-y-4">
                            {stats.recent_sales.map((sale) => (
                                <Link key={sale.id} href={`/pos/sales/${sale.id}`} className="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-4">
                                            <div className="p-2 bg-green-100 rounded-lg">
                                                <Receipt className="h-5 w-5 text-green-600" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900">{sale.sale_number}</p>
                                                <p className="text-sm text-gray-500">{sale.customer?.name || 'Walk-in Customer'}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-semibold text-gray-900">{formatCurrency(sale.total_amount)}</p>
                                            <p className="text-sm text-gray-500">
                                                {new Date(sale.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-8">No recent sales</p>
                    )}
                </CardContent>
            </Card>
        </SectionLayout>
    );
}

