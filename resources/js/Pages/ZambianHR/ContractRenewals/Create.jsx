import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function CreateContractRenewal({ employee }) {
    const { data, setData, post, processing, errors } = useForm({
        current_contract_start: '',
        current_contract_end: '',
        new_contract_start: '',
        new_contract_end: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/zambian-hr/contract-renewals');
    };

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Create Contract Renewal" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/contract-renewals" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Contract Renewals
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Create Contract Renewal</h1>
                    <p className="text-gray-500 mt-1">Renew an employee's fixed-term contract</p>
                </div>

                <Card className="p-6">
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Current Contract Start *
                                    </label>
                                    <input
                                        type="date"
                                        value={data.current_contract_start}
                                        onChange={(e) => setData('current_contract_start', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        required
                                    />
                                    {errors.current_contract_start && <p className="text-red-500 text-sm mt-1">{errors.current_contract_start}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Current Contract End *
                                    </label>
                                    <input
                                        type="date"
                                        value={data.current_contract_end}
                                        onChange={(e) => setData('current_contract_end', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        required
                                    />
                                    {errors.current_contract_end && <p className="text-red-500 text-sm mt-1">{errors.current_contract_end}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        New Contract Start *
                                    </label>
                                    <input
                                        type="date"
                                        value={data.new_contract_start}
                                        onChange={(e) => setData('new_contract_start', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        required
                                    />
                                    {errors.new_contract_start && <p className="text-red-500 text-sm mt-1">{errors.new_contract_start}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        New Contract End *
                                    </label>
                                    <input
                                        type="date"
                                        value={data.new_contract_end}
                                        onChange={(e) => setData('new_contract_end', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        required
                                    />
                                    {errors.new_contract_end && <p className="text-red-500 text-sm mt-1">{errors.new_contract_end}</p>}
                                </div>
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-4">
                            <Link href="/zambian-hr/contract-renewals">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Renewal'}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </SectionLayout>
    );
}

