import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Plus, Heart, ArrowLeft } from 'lucide-react';

export default function FuneralGrantsIndex({ grants }) {
    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Funeral Grants" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/dashboard" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Dashboard
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Funeral Grants</h1>
                            <p className="text-gray-500 mt-1">Manage funeral assistance for employees and beneficiaries</p>
                        </div>
                        <Link href="/zambian-hr/funeral-grants/create">
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                New Funeral Grant
                            </Button>
                        </Link>
                    </div>
                </div>

                <Card className="p-6">
                    {grants && grants.data && grants.data.length > 0 ? (
                        <div className="space-y-4">
                            <p className="text-gray-500">Funeral grants list coming soon...</p>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <Heart className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Funeral Grants Yet</h3>
                            <p className="text-gray-500 mb-6">Create your first funeral grant application</p>
                            <Link href="/zambian-hr/funeral-grants/create">
                                <Button>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Create Funeral Grant
                                </Button>
                            </Link>
                        </div>
                    )}
                </Card>
            </div>
        </SectionLayout>
    );
}

