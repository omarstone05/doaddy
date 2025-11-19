import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Plus, FileText, ArrowLeft } from 'lucide-react';

export default function ContractRenewalsIndex({ renewals }) {
    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Contract Renewals" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/dashboard" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Dashboard
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Contract Renewals</h1>
                            <p className="text-gray-500 mt-1">Manage fixed-term contract renewals</p>
                        </div>
                        <Link href="/zambian-hr/contract-renewals/create">
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                New Renewal
                            </Button>
                        </Link>
                    </div>
                </div>

                <Card className="p-6">
                    {renewals && renewals.data && renewals.data.length > 0 ? (
                        <div className="space-y-4">
                            <p className="text-gray-500">Contract renewals list coming soon...</p>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Contract Renewals Yet</h3>
                            <p className="text-gray-500 mb-6">Create a contract renewal</p>
                            <Link href="/zambian-hr/contract-renewals/create">
                                <Button>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Create Renewal
                                </Button>
                            </Link>
                        </div>
                    )}
                </Card>
            </div>
        </SectionLayout>
    );
}

