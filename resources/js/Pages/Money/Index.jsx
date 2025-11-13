import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { Wallet, TrendingUp, FileText, ShoppingCart, Receipt, Plus, ArrowUp, ArrowDown } from 'lucide-react';

export default function MoneyIndex({ stats, insights }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title="Money" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="Money" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Total Accounts</p>
                                <p className="text-3xl font-bold text-teal-500">{stats?.total_accounts || 0}</p>
                            </div>
                            <div className="p-3 bg-teal-500/10 rounded-lg">
                                <Wallet className="h-6 w-6 text-teal-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Total Balance</p>
                                <p className="text-3xl font-bold text-teal-500">{formatCurrency(stats?.total_balance)}</p>
                            </div>
                            <div className="p-3 bg-teal-500/10 rounded-lg">
                                <TrendingUp className="h-6 w-6 text-teal-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">This Month Income</p>
                                <p className="text-3xl font-bold text-green-500">{formatCurrency(stats?.monthly_income)}</p>
                            </div>
                            <div className="p-3 bg-green-500/10 rounded-lg">
                                <ArrowUp className="h-6 w-6 text-green-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">This Month Expenses</p>
                                <p className="text-3xl font-bold text-red-500">{formatCurrency(stats?.monthly_expenses)}</p>
                            </div>
                            <div className="p-3 bg-red-500/10 rounded-lg">
                                <ArrowDown className="h-6 w-6 text-red-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Quick Actions */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <Link href="/money/accounts/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-teal-500/10 rounded-full mb-4">
                                    <Wallet className="h-8 w-8 text-teal-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Add Account</h3>
                                <p className="text-sm text-gray-600">Create a new money account</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/money/movements/create?type=income" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-green-500/10 rounded-full mb-4">
                                    <ArrowUp className="h-8 w-8 text-green-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Record Income</h3>
                                <p className="text-sm text-gray-600">Add income transaction</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/money/movements/create?type=expense" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-red-500/10 rounded-full mb-4">
                                    <ArrowDown className="h-8 w-8 text-red-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Record Expense</h3>
                                <p className="text-sm text-gray-600">Add expense transaction</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/money/budgets/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-teal-500/10 rounded-full mb-4">
                                    <FileText className="h-8 w-8 text-teal-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Create Budget</h3>
                                <p className="text-sm text-gray-600">Set up a new budget</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            {/* Recent Activity */}
            <Card>
                <CardHeader>
                    <CardTitle>Recent Activity</CardTitle>
                </CardHeader>
                <CardContent>
                    {stats?.recent_movements && stats.recent_movements.length > 0 ? (
                        <div className="space-y-4">
                            {stats.recent_movements.map((movement) => (
                                <div key={movement.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div className="flex items-center gap-4">
                                        <div className={`p-2 rounded-lg ${movement.flow_type === 'income' ? 'bg-green-100' : 'bg-red-100'}`}>
                                            {movement.flow_type === 'income' ? (
                                                <ArrowUp className="h-5 w-5 text-green-600" />
                                            ) : (
                                                <ArrowDown className="h-5 w-5 text-red-600" />
                                            )}
                                        </div>
                                        <div>
                                            <p className="font-medium text-gray-900">{movement.description}</p>
                                            <p className="text-sm text-gray-500">{movement.account?.name || 'N/A'}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className={`font-semibold ${movement.flow_type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                                            {movement.flow_type === 'income' ? '+' : '-'}{formatCurrency(movement.amount)}
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            {new Date(movement.transaction_date).toLocaleDateString()}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-8">No recent activity</p>
                    )}
                </CardContent>
            </Card>
        </SectionLayout>
    );
}

