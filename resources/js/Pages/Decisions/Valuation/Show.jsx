import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, DollarSign, Calendar, User } from 'lucide-react';

export default function BusinessValuationsShow({ valuation }) {
    const formatCurrency = (amount, currency = 'ZMW') => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Business Valuation" />
            <div className="max-w-4xl mx-auto">
                <Link href="/decisions/valuation">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Valuations
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-3">
                            <DollarSign className="h-8 w-8 text-teal-600" />
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Business Valuation</h1>
                                <p className="text-gray-500 mt-1">
                                    {new Date(valuation.valuation_date).toLocaleDateString()}
                                </p>
                            </div>
                        </div>
                        <div className="text-right">
                            <div className="text-3xl font-bold text-teal-600">
                                {formatCurrency(valuation.valuation_amount, valuation.currency)}
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <span className="text-sm font-medium">Valuation Method</span>
                            </div>
                            <p className="text-gray-900 font-medium">
                                {valuation.valuation_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <User className="h-4 w-4" />
                                <span className="text-sm font-medium">Valued By</span>
                            </div>
                            <p className="text-gray-900 font-medium">
                                {valuation.valued_by?.name || 'N/A'}
                            </p>
                        </div>
                    </div>

                    {valuation.method_details && (
                        <div className="mb-6">
                            <div className="text-sm font-medium text-gray-700 mb-2">Method Details</div>
                            <p className="text-gray-900">{valuation.method_details}</p>
                        </div>
                    )}

                    {valuation.assumptions && (
                        <div className="mb-6">
                            <div className="text-sm font-medium text-gray-700 mb-2">Assumptions</div>
                            <p className="text-gray-900">{valuation.assumptions}</p>
                        </div>
                    )}

                    {valuation.notes && (
                        <div className="mb-6">
                            <div className="text-sm font-medium text-gray-700 mb-2">Notes</div>
                            <p className="text-gray-900">{valuation.notes}</p>
                        </div>
                    )}
                </div>

                <div className="flex gap-3">
                    <Link href={`/decisions/valuation/${valuation.id}/edit`}>
                        <Button>Edit Valuation</Button>
                    </Link>
                </div>
            </div>
        </SectionLayout>
    );
}

