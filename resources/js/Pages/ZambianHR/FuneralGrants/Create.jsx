import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function CreateFuneralGrant() {
    const { data, setData, post, processing, errors } = useForm({
        deceased_person: '',
        deceased_name: '',
        relationship_to_employee: '',
        date_of_death: '',
        grant_amount: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/zambian-hr/funeral-grants');
    };

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Create Funeral Grant" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/funeral-grants" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Funeral Grants
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Create Funeral Grant</h1>
                    <p className="text-gray-500 mt-1">Apply for funeral assistance</p>
                </div>

                <Card className="p-6">
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Deceased Person *
                                </label>
                                <select
                                    value={data.deceased_person}
                                    onChange={(e) => setData('deceased_person', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                >
                                    <option value="">Select...</option>
                                    <option value="employee">Employee</option>
                                    <option value="spouse">Spouse</option>
                                    <option value="child">Child</option>
                                    <option value="parent">Parent</option>
                                </select>
                                {errors.deceased_person && <p className="text-red-500 text-sm mt-1">{errors.deceased_person}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Deceased Name *
                                </label>
                                <input
                                    type="text"
                                    value={data.deceased_name}
                                    onChange={(e) => setData('deceased_name', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.deceased_name && <p className="text-red-500 text-sm mt-1">{errors.deceased_name}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Relationship to Employee *
                                </label>
                                <input
                                    type="text"
                                    value={data.relationship_to_employee}
                                    onChange={(e) => setData('relationship_to_employee', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.relationship_to_employee && <p className="text-red-500 text-sm mt-1">{errors.relationship_to_employee}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Date of Death *
                                </label>
                                <input
                                    type="date"
                                    value={data.date_of_death}
                                    onChange={(e) => setData('date_of_death', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.date_of_death && <p className="text-red-500 text-sm mt-1">{errors.date_of_death}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Grant Amount (ZMW) *
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    value={data.grant_amount}
                                    onChange={(e) => setData('grant_amount', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.grant_amount && <p className="text-red-500 text-sm mt-1">{errors.grant_amount}</p>}
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-4">
                            <Link href="/zambian-hr/funeral-grants">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Funeral Grant'}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </SectionLayout>
    );
}

