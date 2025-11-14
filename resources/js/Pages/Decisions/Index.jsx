import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { DecisionsInsightCard } from '@/Components/sections/DecisionsInsightCard';
import { Target, BarChart3, FolderKanban, TrendingUp, Plus } from 'lucide-react';

export default function DecisionsIndex({ stats, insights }) {
    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Decisions" />
            
            {/* Addy Insights Card - White with Mint Gradient */}
            <DecisionsInsightCard 
                sectionName="Decisions" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Active OKRs</p>
                                <p className="text-3xl font-bold text-teal-500">{stats?.active_okrs || 0}</p>
                            </div>
                            <div className="p-3 bg-teal-500/10 rounded-lg">
                                <Target className="h-6 w-6 text-teal-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Active Projects</p>
                                <p className="text-3xl font-bold text-green-500">{stats?.active_projects || 0}</p>
                            </div>
                            <div className="p-3 bg-green-500/10 rounded-lg">
                                <FolderKanban className="h-6 w-6 text-green-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Strategic Goals</p>
                                <p className="text-3xl font-bold text-blue-500">{stats?.strategic_goals || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-500/10 rounded-lg">
                                <Target className="h-6 w-6 text-blue-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Reports</p>
                                <p className="text-3xl font-bold text-amber-500">{stats?.reports || 0}</p>
                            </div>
                            <div className="p-3 bg-amber-500/10 rounded-lg">
                                <BarChart3 className="h-6 w-6 text-amber-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Quick Actions */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <Link href="/decisions/okrs/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-teal-500/10 rounded-full mb-4">
                                    <Target className="h-8 w-8 text-teal-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Create OKR</h3>
                                <p className="text-sm text-gray-600">Set objectives and key results</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/projects/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-green-500/10 rounded-full mb-4">
                                    <FolderKanban className="h-8 w-8 text-green-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">New Project</h3>
                                <p className="text-sm text-gray-600">Start a new project</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/decisions/goals/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-blue-500/10 rounded-full mb-4">
                                    <Target className="h-8 w-8 text-blue-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Strategic Goal</h3>
                                <p className="text-sm text-gray-600">Define strategic goals</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/reports" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-amber-500/10 rounded-full mb-4">
                                    <BarChart3 className="h-8 w-8 text-amber-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">View Reports</h3>
                                <p className="text-sm text-gray-600">Access all reports</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </SectionLayout>
    );
}

