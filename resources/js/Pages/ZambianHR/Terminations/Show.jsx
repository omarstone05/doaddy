import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, UserX } from 'lucide-react';

export default function ShowTermination({ termination }) {
    if (!termination) {
        return (
            <SectionLayout sectionName="Zambian HR">
                <Head title="Termination Not Found" />
                <div className="max-w-4xl mx-auto">
                    <Card className="p-6 text-center">
                        <p className="text-gray-500">Termination not found</p>
                        <Link href="/zambian-hr/terminations" className="mt-4 inline-block">
                            <Button>Back to Terminations</Button>
                        </Link>
                    </Card>
                </div>
            </SectionLayout>
        );
    }

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Termination Details" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/terminations" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Terminations
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Termination Details</h1>
                </div>

                <Card className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Termination Type</h3>
                            <p className="text-gray-900 capitalize">{termination.termination_type?.replace('_', ' ') || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Termination Date</h3>
                            <p className="text-gray-900">
                                {termination.termination_date ? new Date(termination.termination_date).toLocaleDateString() : 'N/A'}
                            </p>
                        </div>
                        <div className="md:col-span-2">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Reason</h3>
                            <p className="text-gray-900">{termination.reason_details || 'N/A'}</p>
                        </div>
                        {termination.net_settlement_amount && (
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Net Settlement Amount</h3>
                                <p className="text-2xl font-bold text-teal-600">
                                    ZMW {parseFloat(termination.net_settlement_amount).toLocaleString()}
                                </p>
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

