import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function CreateTermination({ employee }) {
    const { data, setData, post, processing, errors } = useForm({
        termination_type: '',
        termination_date: '',
        reason_details: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/zambian-hr/terminations');
    };

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Create Termination" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/terminations" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Terminations
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Create Termination</h1>
                    <p className="text-gray-500 mt-1">Process employee termination with Zambian compliance</p>
                </div>

                <Card className="p-6">
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Termination Type *
                                </label>
                                <select
                                    value={data.termination_type}
                                    onChange={(e) => setData('termination_type', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                >
                                    <option value="">Select...</option>
                                    <option value="resignation">Resignation</option>
                                    <option value="notice">Notice</option>
                                    <option value="medical_discharge">Medical Discharge</option>
                                    <option value="redundancy">Redundancy</option>
                                    <option value="summary_dismissal">Summary Dismissal</option>
                                    <option value="retirement">Retirement</option>
                                    <option value="contract_expiry">Contract Expiry</option>
                                </select>
                                {errors.termination_type && <p className="text-red-500 text-sm mt-1">{errors.termination_type}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Termination Date *
                                </label>
                                <input
                                    type="date"
                                    value={data.termination_date}
                                    onChange={(e) => setData('termination_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.termination_date && <p className="text-red-500 text-sm mt-1">{errors.termination_date}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Reason Details
                                </label>
                                <textarea
                                    value={data.reason_details}
                                    onChange={(e) => setData('reason_details', e.target.value)}
                                    rows={4}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                />
                                {errors.reason_details && <p className="text-red-500 text-sm mt-1">{errors.reason_details}</p>}
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-4">
                            <Link href="/zambian-hr/terminations">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Processing...' : 'Create Termination'}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </SectionLayout>
    );
}

