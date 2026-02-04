import { Head, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function ProspectsEdit({ prospect }) {
    const { data, setData, put, processing, errors } = useForm({
        name: prospect?.name || '',
        company_name: prospect?.company_name || '',
        email: prospect?.email || '',
        phone: prospect?.phone || '',
        stage: prospect?.stage || 'lead',
        estimated_value: prospect?.estimated_value || '',
        probability: prospect?.probability ?? '',
        currency: prospect?.currency || 'ZMW',
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/prospects/${prospect.id}`);
    };

    if (!prospect) {
        return (
            <SectionLayout sectionName="Sales">
                <Head title="Prospect Not Found" />
                <div className="text-center py-12">
                    <h1 className="text-2xl font-bold text-gray-900 mb-4">Prospect not found</h1>
                    <Button variant="secondary" onClick={() => window.history.back()}>
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Go Back
                    </Button>
                </div>
            </SectionLayout>
        );
    }

    return (
        <SectionLayout sectionName="Sales">
            <Head title={`Edit Prospect - ${prospect.name || prospect.company_name}`} />
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
                    <h1 className="text-3xl font-bold text-gray-900">Edit Prospect</h1>
                    <p className="text-gray-500 mt-1">Update prospect details</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div>
                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                Contact Name *
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                        </div>

                        <div>
                            <label htmlFor="company_name" className="block text-sm font-medium text-gray-700 mb-2">
                                Company Name
                            </label>
                            <input
                                id="company_name"
                                type="text"
                                value={data.company_name}
                                onChange={(e) => setData('company_name', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                                {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                            </div>

                            <div>
                                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-2">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    type="text"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                                {errors.phone && <p className="mt-1 text-sm text-red-600">{errors.phone}</p>}
                            </div>
                        </div>

                        <div>
                            <label htmlFor="stage" className="block text-sm font-medium text-gray-700 mb-2">
                                Stage *
                            </label>
                            <select
                                id="stage"
                                value={data.stage}
                                onChange={(e) => setData('stage', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="lead">Lead</option>
                                <option value="contacted">Contacted</option>
                                <option value="qualified">Qualified</option>
                                <option value="proposal">Proposal</option>
                                <option value="negotiation">Negotiation</option>
                                <option value="won">Won</option>
                                <option value="lost">Lost</option>
                            </select>
                            {errors.stage && <p className="mt-1 text-sm text-red-600">{errors.stage}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="estimated_value" className="block text-sm font-medium text-gray-700 mb-2">
                                    Estimated Value
                                </label>
                                <input
                                    id="estimated_value"
                                    type="number"
                                    step="0.01"
                                    value={data.estimated_value}
                                    onChange={(e) => setData('estimated_value', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="0.00"
                                />
                                {errors.estimated_value && <p className="mt-1 text-sm text-red-600">{errors.estimated_value}</p>}
                            </div>

                            <div>
                                <label htmlFor="probability" className="block text-sm font-medium text-gray-700 mb-2">
                                    Probability (%)
                                </label>
                                <input
                                    id="probability"
                                    type="number"
                                    min="0"
                                    max="100"
                                    value={data.probability}
                                    onChange={(e) => setData('probability', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="0"
                                />
                                {errors.probability && <p className="mt-1 text-sm text-red-600">{errors.probability}</p>}
                            </div>
                        </div>

                        <div className="flex gap-4 pt-4">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Update Prospect
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
