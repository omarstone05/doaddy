import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function CreateConflictOfInterest() {
    const { data, setData, post, processing, errors } = useForm({
        declaration_type: '',
        organization_name: '',
        nature_of_interest: '',
        start_date: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/zambian-hr/conflict-of-interest');
    };

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Create Conflict of Interest Declaration" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/conflict-of-interest" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Conflict of Interest
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Create Declaration</h1>
                    <p className="text-gray-500 mt-1">Declare a potential conflict of interest</p>
                </div>

                <Card className="p-6">
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Declaration Type *
                                </label>
                                <select
                                    value={data.declaration_type}
                                    onChange={(e) => setData('declaration_type', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                >
                                    <option value="">Select...</option>
                                    <option value="outside_employment">Outside Employment</option>
                                    <option value="board_membership">Board Membership</option>
                                    <option value="business_interest">Business Interest</option>
                                    <option value="family_business">Family Business</option>
                                    <option value="shareholding">Shareholding</option>
                                    <option value="consultancy">Consultancy</option>
                                    <option value="other">Other</option>
                                </select>
                                {errors.declaration_type && <p className="text-red-500 text-sm mt-1">{errors.declaration_type}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Organization Name *
                                </label>
                                <input
                                    type="text"
                                    value={data.organization_name}
                                    onChange={(e) => setData('organization_name', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.organization_name && <p className="text-red-500 text-sm mt-1">{errors.organization_name}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Nature of Interest *
                                </label>
                                <textarea
                                    value={data.nature_of_interest}
                                    onChange={(e) => setData('nature_of_interest', e.target.value)}
                                    rows={4}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.nature_of_interest && <p className="text-red-500 text-sm mt-1">{errors.nature_of_interest}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Start Date *
                                </label>
                                <input
                                    type="date"
                                    value={data.start_date}
                                    onChange={(e) => setData('start_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.start_date && <p className="text-red-500 text-sm mt-1">{errors.start_date}</p>}
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-4">
                            <Link href="/zambian-hr/conflict-of-interest">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Submitting...' : 'Submit Declaration'}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </SectionLayout>
    );
}

