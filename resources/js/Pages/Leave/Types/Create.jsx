import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function LeaveTypesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        maximum_days_per_year: 0,
        can_carry_forward: false,
        max_carry_forward_days: null,
        is_active: true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/leave/types');
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Create Leave Type" />
            <div className="max-w-3xl mx-auto ">
                <Link href="/leave/types">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Leave Types
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <h1 className="text-2xl font-bold text-gray-900 mb-6">Create Leave Type</h1>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Name <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="e.g., Annual Leave, Sick Leave"
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
                                placeholder="Describe this leave type..."
                            />
                            {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Maximum Days Per Year <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                value={data.maximum_days_per_year}
                                onChange={(e) => setData('maximum_days_per_year', parseInt(e.target.value) || 0)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                min="0"
                                required
                            />
                            {errors.maximum_days_per_year && <p className="mt-1 text-sm text-red-600">{errors.maximum_days_per_year}</p>}
                        </div>

                        <div className="flex items-start">
                            <input
                                type="checkbox"
                                id="can_carry_forward"
                                checked={data.can_carry_forward}
                                onChange={(e) => setData('can_carry_forward', e.target.checked)}
                                className="mt-1 h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                            />
                            <label htmlFor="can_carry_forward" className="ml-3 text-sm font-medium text-gray-700">
                                Allow carry forward
                            </label>
                        </div>

                        {data.can_carry_forward && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Maximum Carry Forward Days
                                </label>
                                <input
                                    type="number"
                                    value={data.max_carry_forward_days || ''}
                                    onChange={(e) => setData('max_carry_forward_days', e.target.value ? parseInt(e.target.value) : null)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    min="0"
                                />
                                {errors.max_carry_forward_days && <p className="mt-1 text-sm text-red-600">{errors.max_carry_forward_days}</p>}
                            </div>
                        )}

                        <div className="flex items-start">
                            <input
                                type="checkbox"
                                id="is_active"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="mt-1 h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                            />
                            <label htmlFor="is_active" className="ml-3 text-sm font-medium text-gray-700">
                                Active
                            </label>
                        </div>

                        <div className="flex gap-3 pt-4">
                            <Link href="/leave/types">
                                <Button variant="secondary" type="button">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Leave Type'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </SectionLayout>
    );
}

