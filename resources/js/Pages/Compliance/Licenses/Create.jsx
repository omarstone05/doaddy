import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function LicensesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        license_number: '',
        name: '',
        description: '',
        category: '',
        issuing_authority: '',
        issue_date: '',
        expiry_date: '',
        status: 'active',
        is_renewable: true,
        renewal_date: '',
        notes: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/compliance/licenses');
    };

    return (
        <SectionLayout sectionName="Compliance">
            <Head title="Create License" />
            <div className="max-w-3xl mx-auto ">
                <Link href="/compliance/licenses">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Licenses
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <h1 className="text-2xl font-bold text-gray-900 mb-6">Create License</h1>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                License Number <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.license_number}
                                onChange={(e) => setData('license_number', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="e.g., BIZ-2024-001"
                                required
                            />
                            {errors.license_number && <p className="mt-1 text-sm text-red-600">{errors.license_number}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Name <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="e.g., Business License"
                                required
                            />
                            {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="License description..."
                            />
                            {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <input
                                    type="text"
                                    value={data.category}
                                    onChange={(e) => setData('category', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="e.g., Business, Professional"
                                />
                                {errors.category && <p className="mt-1 text-sm text-red-600">{errors.category}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Issuing Authority <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.issuing_authority}
                                    onChange={(e) => setData('issuing_authority', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="e.g., Ministry of Commerce"
                                    required
                                />
                                {errors.issuing_authority && <p className="mt-1 text-sm text-red-600">{errors.issuing_authority}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Issue Date <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    value={data.issue_date}
                                    onChange={(e) => setData('issue_date', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.issue_date && <p className="mt-1 text-sm text-red-600">{errors.issue_date}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Expiry Date <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    value={data.expiry_date}
                                    onChange={(e) => setData('expiry_date', e.target.value)}
                                    min={data.issue_date}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.expiry_date && <p className="mt-1 text-sm text-red-600">{errors.expiry_date}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Status <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="pending_renewal">Pending Renewal</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                                {errors.status && <p className="mt-1 text-sm text-red-600">{errors.status}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Renewal Date</label>
                                <input
                                    type="date"
                                    value={data.renewal_date}
                                    onChange={(e) => setData('renewal_date', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                                {errors.renewal_date && <p className="mt-1 text-sm text-red-600">{errors.renewal_date}</p>}
                            </div>
                        </div>

                        <div className="flex items-start">
                            <input
                                type="checkbox"
                                id="is_renewable"
                                checked={data.is_renewable}
                                onChange={(e) => setData('is_renewable', e.target.checked)}
                                className="mt-1 h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                            />
                            <label htmlFor="is_renewable" className="ml-3 text-sm font-medium text-gray-700">
                                Is Renewable
                            </label>
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
                            <Link href="/compliance/licenses">
                                <Button variant="secondary" type="button">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating...' : 'Create License'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </SectionLayout>
    );
}

