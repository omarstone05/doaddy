import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, FileText } from 'lucide-react';

export default function ShowContractRenewal({ renewal }) {
    if (!renewal) {
        return (
            <SectionLayout sectionName="Zambian HR">
                <Head title="Contract Renewal Not Found" />
                <div className="max-w-4xl mx-auto">
                    <Card className="p-6 text-center">
                        <p className="text-gray-500">Contract renewal not found</p>
                        <Link href="/zambian-hr/contract-renewals" className="mt-4 inline-block">
                            <Button>Back to Contract Renewals</Button>
                        </Link>
                    </Card>
                </div>
            </SectionLayout>
        );
    }

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Contract Renewal Details" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/contract-renewals" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Contract Renewals
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Contract Renewal Details</h1>
                </div>

                <Card className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Current Contract Start</h3>
                            <p className="text-gray-900">
                                {renewal.current_contract_start ? new Date(renewal.current_contract_start).toLocaleDateString() : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Current Contract End</h3>
                            <p className="text-gray-900">
                                {renewal.current_contract_end ? new Date(renewal.current_contract_end).toLocaleDateString() : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">New Contract Start</h3>
                            <p className="text-gray-900">
                                {renewal.new_contract_start ? new Date(renewal.new_contract_start).toLocaleDateString() : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">New Contract End</h3>
                            <p className="text-gray-900">
                                {renewal.new_contract_end ? new Date(renewal.new_contract_end).toLocaleDateString() : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Renewal Status</h3>
                            <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                renewal.renewal_status === 'accepted' ? 'bg-green-100 text-green-700' :
                                renewal.renewal_status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                renewal.renewal_status === 'rejected' ? 'bg-red-100 text-red-700' :
                                'bg-gray-100 text-gray-700'
                            }`}>
                                {renewal.renewal_status ? renewal.renewal_status.charAt(0).toUpperCase() + renewal.renewal_status.slice(1) : 'N/A'}
                            </span>
                        </div>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

