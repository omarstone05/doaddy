import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { UserX, ArrowLeft } from 'lucide-react';

export default function TerminationsIndex({ terminations }) {
    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Terminations" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/dashboard" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Dashboard
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Terminations</h1>
                    <p className="text-gray-500 mt-1">Manage employee terminations with Zambian compliance</p>
                </div>

                <Card className="p-6">
                    {terminations && terminations.data && terminations.data.length > 0 ? (
                        <div className="space-y-4">
                            <p className="text-gray-500">Terminations list coming soon...</p>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <UserX className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Terminations Yet</h3>
                            <p className="text-gray-500">Termination records will appear here</p>
                        </div>
                    )}
                </Card>
            </div>
        </SectionLayout>
    );
}

