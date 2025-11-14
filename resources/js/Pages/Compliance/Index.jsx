import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { Shield, FileText, FileCheck, Receipt, Bell, Building2, Plus } from 'lucide-react';

export default function ComplianceIndex({ stats, insights }) {
    return (
        <SectionLayout sectionName="Compliance">
            <Head title="Compliance" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="Compliance" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Documents</p>
                                <p className="text-3xl font-bold text-teal-500">{stats?.total_documents || 0}</p>
                            </div>
                            <div className="p-3 bg-teal-500/10 rounded-lg">
                                <FileText className="h-6 w-6 text-teal-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Active Licenses</p>
                                <p className="text-3xl font-bold text-green-500">{stats?.active_licenses || 0}</p>
                            </div>
                            <div className="p-3 bg-green-500/10 rounded-lg">
                                <FileCheck className="h-6 w-6 text-green-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Expiring Soon</p>
                                <p className="text-3xl font-bold text-amber-500">{stats?.expiring_soon || 0}</p>
                            </div>
                            <div className="p-3 bg-amber-500/10 rounded-lg">
                                <Shield className="h-6 w-6 text-amber-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Audit Logs</p>
                                <p className="text-3xl font-bold text-blue-500">{stats?.audit_logs || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-500/10 rounded-lg">
                                <Shield className="h-6 w-6 text-blue-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Quick Actions */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <Link href="/compliance/documents/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-teal-500/10 rounded-full mb-4">
                                    <FileText className="h-8 w-8 text-teal-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Add Document</h3>
                                <p className="text-sm text-gray-600">Upload a document</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/compliance/licenses/create" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-green-500/10 rounded-full mb-4">
                                    <FileCheck className="h-8 w-8 text-green-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Add License</h3>
                                <p className="text-sm text-gray-600">Register a license</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/activity-logs" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-blue-500/10 rounded-full mb-4">
                                    <Shield className="h-8 w-8 text-blue-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Audit Trail</h3>
                                <p className="text-sm text-gray-600">View activity logs</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/settings" className="block">
                    <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center">
                                <div className="p-4 bg-amber-500/10 rounded-full mb-4">
                                    <Building2 className="h-8 w-8 text-amber-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Settings</h3>
                                <p className="text-sm text-gray-600">Manage settings</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </SectionLayout>
    );
}

