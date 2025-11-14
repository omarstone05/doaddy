import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { Users, Wallet, Calendar, Briefcase, DollarSign, Plus, Building2 } from 'lucide-react';

export default function PeopleIndex({ stats, insights }) {
    return (
        <SectionLayout sectionName="People">
            <Head title="People" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="People" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Total Team</p>
                                <p className="text-3xl font-bold text-teal-500">{stats?.total_team || 0}</p>
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
                                <p className="text-sm font-medium text-gray-600 mb-1">Active Payroll</p>
                                <p className="text-3xl font-bold text-green-500">{stats?.active_payroll || 0}</p>
                            </div>
                            <div className="p-3 bg-green-500/10 rounded-lg">
                                <Wallet className="h-6 w-6 text-green-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Pending Leave</p>
                                <p className="text-3xl font-bold text-amber-500">{stats?.pending_leave || 0}</p>
                            </div>
                            <div className="p-3 bg-amber-500/10 rounded-lg">
                                <Calendar className="h-6 w-6 text-amber-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Commission Rules</p>
                                <p className="text-3xl font-bold text-blue-500">{stats?.commission_rules || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-500/10 rounded-lg">
                                <DollarSign className="h-6 w-6 text-blue-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Quick Actions */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <Link href="/team/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-teal-500/10 rounded-full mb-4">
                                    <Users className="h-8 w-8 text-teal-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Add Team Member</h3>
                                <p className="text-sm text-gray-600">Add a new team member</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/departments/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-purple-500/10 rounded-full mb-4">
                                    <Building2 className="h-8 w-8 text-purple-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Add Department</h3>
                                <p className="text-sm text-gray-600">Create a new department</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/payroll/runs/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-green-500/10 rounded-full mb-4">
                                    <Wallet className="h-8 w-8 text-green-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Run Payroll</h3>
                                <p className="text-sm text-gray-600">Process payroll</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/leave/requests/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-amber-500/10 rounded-full mb-4">
                                    <Calendar className="h-8 w-8 text-amber-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Request Leave</h3>
                                <p className="text-sm text-gray-600">Submit a leave request</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/commissions/rules/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-blue-500/10 rounded-full mb-4">
                                    <DollarSign className="h-8 w-8 text-blue-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Commission Rule</h3>
                                <p className="text-sm text-gray-600">Create commission rule</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </SectionLayout>
    );
}

