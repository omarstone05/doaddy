import { Head, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function QuotationsCreate({ customers, prospects }) {
    const { data, setData, post, processing, errors } = useForm({
        prospect_id: '',
        customer_id: '',
        title: '',
        issue_date: new Date().toISOString().split('T')[0],
        valid_until: '',
        currency: 'USD',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/quotations');
    };

    return (
        <SectionLayout sectionName="Sales">
            <Head title="Create Quotation" />
            <div className="max-w-2xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Create Quotation</h1>
                    <p className="text-gray-500 mt-1">Create a new quotation for a customer or prospect</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="customer_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Customer
                                </label>
                                <select
                                    id="customer_id"
                                    value={data.customer_id}
                                    onChange={(e) => {
                                        setData('customer_id', e.target.value);
                                        setData('prospect_id', ''); // Clear prospect if customer selected
                                    }}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="">Select customer</option>
                                    {customers.map((customer) => (
                                        <option key={customer.id} value={customer.id}>
                                            {customer.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.customer_id && <p className="mt-1 text-sm text-red-600">{errors.customer_id}</p>}
                            </div>

                            <div>
                                <label htmlFor="prospect_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Prospect
                                </label>
                                <select
                                    id="prospect_id"
                                    value={data.prospect_id}
                                    onChange={(e) => {
                                        setData('prospect_id', e.target.value);
                                        setData('customer_id', ''); // Clear customer if prospect selected
                                    }}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="">Select prospect</option>
                                    {prospects.map((prospect) => (
                                        <option key={prospect.id} value={prospect.id}>
                                            {prospect.company_name || prospect.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.prospect_id && <p className="mt-1 text-sm text-red-600">{errors.prospect_id}</p>}
                            </div>
                        </div>

                        <div>
                            <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
                                Quotation Title *
                            </label>
                            <input
                                id="title"
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="issue_date" className="block text-sm font-medium text-gray-700 mb-2">
                                    Issue Date *
                                </label>
                                <input
                                    id="issue_date"
                                    type="date"
                                    value={data.issue_date}
                                    onChange={(e) => setData('issue_date', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.issue_date && <p className="mt-1 text-sm text-red-600">{errors.issue_date}</p>}
                            </div>

                            <div>
                                <label htmlFor="valid_until" className="block text-sm font-medium text-gray-700 mb-2">
                                    Valid Until *
                                </label>
                                <input
                                    id="valid_until"
                                    type="date"
                                    value={data.valid_until}
                                    onChange={(e) => setData('valid_until', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.valid_until && <p className="mt-1 text-sm text-red-600">{errors.valid_until}</p>}
                            </div>
                        </div>

                        <div>
                            <label htmlFor="currency" className="block text-sm font-medium text-gray-700 mb-2">
                                Currency
                            </label>
                            <select
                                id="currency"
                                value={data.currency}
                                onChange={(e) => setData('currency', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="USD">USD</option>
                                <option value="ZMW">ZMW</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>

                        <div className="flex gap-4 pt-4">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Create Quotation
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => window.history.back()}
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </SectionLayout>
    );
}

