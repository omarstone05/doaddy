import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Shield } from 'lucide-react';

export default function ShowConflictOfInterest({ declaration }) {
    if (!declaration) {
        return (
            <SectionLayout sectionName="Zambian HR">
                <Head title="Declaration Not Found" />
                <div className="max-w-4xl mx-auto">
                    <Card className="p-6 text-center">
                        <p className="text-gray-500">Conflict of interest declaration not found</p>
                        <Link href="/zambian-hr/conflict-of-interest" className="mt-4 inline-block">
                            <Button>Back to Declarations</Button>
                        </Link>
                    </Card>
                </div>
            </SectionLayout>
        );
    }

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title={`Conflict of Interest - ${declaration.organization_name || ''}`} />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/conflict-of-interest" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Declarations
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Conflict of Interest Declaration</h1>
                </div>

                <Card className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Declaration Type</h3>
                            <p className="text-gray-900 capitalize">{declaration.declaration_type?.replace('_', ' ') || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Organization Name</h3>
                            <p className="text-gray-900">{declaration.organization_name || 'N/A'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Nature of Interest</h3>
                            <p className="text-gray-900 whitespace-pre-wrap">{declaration.nature_of_interest || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Start Date</h3>
                            <p className="text-gray-900">
                                {declaration.start_date ? new Date(declaration.start_date).toLocaleDateString() : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Status</h3>
                            <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                declaration.status === 'approved' ? 'bg-green-100 text-green-700' :
                                declaration.status === 'under_review' ? 'bg-yellow-100 text-yellow-700' :
                                declaration.status === 'rejected' ? 'bg-red-100 text-red-700' :
                                'bg-gray-100 text-gray-700'
                            }`}>
                                {declaration.status ? declaration.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A'}
                            </span>
                        </div>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

