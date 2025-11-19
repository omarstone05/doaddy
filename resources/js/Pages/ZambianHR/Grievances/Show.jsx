import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, AlertTriangle } from 'lucide-react';

export default function ShowGrievance({ grievance }) {
    if (!grievance) {
        return (
            <SectionLayout sectionName="Zambian HR">
                <Head title="Grievance Not Found" />
                <div className="max-w-4xl mx-auto">
                    <Card className="p-6 text-center">
                        <p className="text-gray-500">Grievance not found</p>
                        <Link href="/zambian-hr/grievances" className="mt-4 inline-block">
                            <Button>Back to Grievances</Button>
                        </Link>
                    </Card>
                </div>
            </SectionLayout>
        );
    }

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title={`Grievance - ${grievance.subject || ''}`} />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/grievances" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Grievances
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">{grievance.subject || 'Grievance Details'}</h1>
                </div>

                <Card className="p-6">
                    <div className="space-y-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Grievance Number</h3>
                            <p className="text-gray-900">{grievance.grievance_number || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Category</h3>
                            <p className="text-gray-900 capitalize">{grievance.grievance_category?.replace('_', ' ') || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Description</h3>
                            <p className="text-gray-900 whitespace-pre-wrap">{grievance.description || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Status</h3>
                            <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                grievance.status === 'resolved' ? 'bg-green-100 text-green-700' :
                                grievance.status === 'under_investigation' ? 'bg-yellow-100 text-yellow-700' :
                                'bg-gray-100 text-gray-700'
                            }`}>
                                {grievance.status ? grievance.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A'}
                            </span>
                        </div>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

