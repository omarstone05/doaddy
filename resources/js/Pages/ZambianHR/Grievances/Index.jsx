import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Plus, AlertTriangle, ArrowLeft } from 'lucide-react';

export default function GrievancesIndex({ grievances }) {
    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Grievances" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/dashboard" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Dashboard
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Grievances</h1>
                            <p className="text-gray-500 mt-1">Manage employee grievances and complaints</p>
                        </div>
                        <Link href="/zambian-hr/grievances/create">
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                File Grievance
                            </Button>
                        </Link>
                    </div>
                </div>

                <Card className="p-6">
                    {grievances && grievances.data && grievances.data.length > 0 ? (
                        <div className="space-y-4">
                            <p className="text-gray-500">Grievances list coming soon...</p>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <AlertTriangle className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Grievances Yet</h3>
                            <p className="text-gray-500 mb-6">File a grievance to start the process</p>
                            <Link href="/zambian-hr/grievances/create">
                                <Button>
                                    <Plus className="h-4 w-4 mr-2" />
                                    File Grievance
                                </Button>
                            </Link>
                        </div>
                    )}
                </Card>
            </div>
        </SectionLayout>
    );
}

