import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function OKRsEdit({ okr, users }) {
    const { data, setData, put, processing, errors } = useForm({
        title: okr.title || '',
        description: okr.description || '',
        quarter: okr.quarter || '',
        status: okr.status || 'draft',
        owner_id: okr.owner_id || '',
        start_date: okr.start_date || '',
        end_date: okr.end_date || '',
        notes: okr.notes || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/decisions/okrs/${okr.id}`);
    };

    const currentYear = new Date().getFullYear();
    const quarters = [];
    for (let year = currentYear - 1; year <= currentYear + 1; year++) {
        for (let q = 1; q <= 4; q++) {
            quarters.push(`${year}-Q${q}`);
        }
    }

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Edit OKR" />
            <div className="max-w-3xl mx-auto ">
                <Link href="/decisions/okrs">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to OKRs
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <h1 className="text-2xl font-bold text-gray-900 mb-6">Edit OKR</h1>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Objective Title <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                            {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Quarter <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.quarter}
                                    onChange={(e) => setData('quarter', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Select Quarter</option>
                                    {quarters.map((q) => (
                                        <option key={q} value={q}>
                                            {q}
                                        </option>
                                    ))}
                                </select>
                                {errors.quarter && <p className="mt-1 text-sm text-red-600">{errors.quarter}</p>}
                            </div>

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
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                {errors.status && <p className="mt-1 text-sm text-red-600">{errors.status}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Start Date <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    value={data.start_date}
                                    onChange={(e) => setData('start_date', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.start_date && <p className="mt-1 text-sm text-red-600">{errors.start_date}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    End Date <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    value={data.end_date}
                                    onChange={(e) => setData('end_date', e.target.value)}
                                    min={data.start_date}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.end_date && <p className="mt-1 text-sm text-red-600">{errors.end_date}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Owner</label>
                            <select
                                value={data.owner_id}
                                onChange={(e) => setData('owner_id', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="">No Owner</option>
                                {users.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name}
                                    </option>
                                ))}
                            </select>
                            {errors.owner_id && <p className="mt-1 text-sm text-red-600">{errors.owner_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                            {errors.notes && <p className="mt-1 text-sm text-red-600">{errors.notes}</p>}
                        </div>

                        <div className="flex gap-3 pt-4">
                            <Link href="/decisions/okrs">
                                <Button variant="secondary" type="button">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Updating...' : 'Update OKR'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </SectionLayout>
    );
}

