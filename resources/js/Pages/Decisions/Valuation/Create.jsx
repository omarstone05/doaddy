import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function BusinessValuationsCreate({ users }) {
    const { data, setData, post, processing, errors } = useForm({
        valuation_date: '',
        valuation_amount: '',
        currency: 'ZMW',
        valuation_method: 'revenue_multiple',
        method_details: '',
        assumptions: '',
        notes: '',
        valued_by_id: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/decisions/valuation');
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Create Business Valuation" />
            <div className="max-w-3xl mx-auto ">
                <Link href="/decisions/valuation">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Valuations
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <h1 className="text-2xl font-bold text-gray-900 mb-6">Create Business Valuation</h1>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Valuation Date <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    value={data.valuation_date}
                                    onChange={(e) => setData('valuation_date', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.valuation_date && <p className="mt-1 text-sm text-red-600">{errors.valuation_date}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Currency <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.currency}
                                    onChange={(e) => setData('currency', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="ZMW">ZMW</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                </select>
                                {errors.currency && <p className="mt-1 text-sm text-red-600">{errors.currency}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Valuation Amount <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                value={data.valuation_amount}
                                onChange={(e) => setData('valuation_amount', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="0.00"
                                required
                            />
                            {errors.valuation_amount && <p className="mt-1 text-sm text-red-600">{errors.valuation_amount}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Valuation Method <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={data.valuation_method}
                                onChange={(e) => setData('valuation_method', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="revenue_multiple">Revenue Multiple</option>
                                <option value="ebitda_multiple">EBITDA Multiple</option>
                                <option value="asset_based">Asset Based</option>
                                <option value="discounted_cash_flow">Discounted Cash Flow</option>
                                <option value="market_comparable">Market Comparable</option>
                                <option value="other">Other</option>
                            </select>
                            {errors.valuation_method && <p className="mt-1 text-sm text-red-600">{errors.valuation_method}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Method Details</label>
                            <textarea
                                value={data.method_details}
                                onChange={(e) => setData('method_details', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="Describe the valuation method used..."
                            />
                            {errors.method_details && <p className="mt-1 text-sm text-red-600">{errors.method_details}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Assumptions</label>
                            <textarea
                                value={data.assumptions}
                                onChange={(e) => setData('assumptions', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="Key assumptions made in the valuation..."
                            />
                            {errors.assumptions && <p className="mt-1 text-sm text-red-600">{errors.assumptions}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Valued By</label>
                            <select
                                value={data.valued_by_id}
                                onChange={(e) => setData('valued_by_id', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="">Select Valuer</option>
                                {users.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name}
                                    </option>
                                ))}
                            </select>
                            {errors.valued_by_id && <p className="mt-1 text-sm text-red-600">{errors.valued_by_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="Additional notes..."
                            />
                            {errors.notes && <p className="mt-1 text-sm text-red-600">{errors.notes}</p>}
                        </div>

                        <div className="flex gap-3 pt-4">
                            <Link href="/decisions/valuation">
                                <Button variant="secondary" type="button">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Valuation'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </SectionLayout>
    );
}

