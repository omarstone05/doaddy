import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Heart } from 'lucide-react';

export default function ShowFuneralGrant({ grant }) {
    if (!grant) {
        return (
            <SectionLayout sectionName="Zambian HR">
                <Head title="Funeral Grant Not Found" />
                <div className="max-w-4xl mx-auto">
                    <Card className="p-6 text-center">
                        <p className="text-gray-500">Funeral grant not found</p>
                        <Link href="/zambian-hr/funeral-grants" className="mt-4 inline-block">
                            <Button>Back to Funeral Grants</Button>
                        </Link>
                    </Card>
                </div>
            </SectionLayout>
        );
    }

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title={`Funeral Grant - ${grant.deceased_name || ''}`} />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/funeral-grants" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Funeral Grants
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Funeral Grant Details</h1>
                </div>

                <Card className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Deceased Person</h3>
                            <p className="text-gray-900 capitalize">{grant.deceased_person || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Deceased Name</h3>
                            <p className="text-gray-900">{grant.deceased_name || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Relationship</h3>
                            <p className="text-gray-900">{grant.relationship_to_employee || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Date of Death</h3>
                            <p className="text-gray-900">
                                {grant.date_of_death ? new Date(grant.date_of_death).toLocaleDateString() : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Grant Amount</h3>
                            <p className="text-gray-900">
                                {grant.grant_amount ? `ZMW ${parseFloat(grant.grant_amount).toLocaleString()}` : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Status</h3>
                            <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                grant.status === 'approved' ? 'bg-green-100 text-green-700' :
                                grant.status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                grant.status === 'rejected' ? 'bg-red-100 text-red-700' :
                                'bg-gray-100 text-gray-700'
                            }`}>
                                {grant.status ? grant.status.charAt(0).toUpperCase() + grant.status.slice(1) : 'N/A'}
                            </span>
                        </div>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

