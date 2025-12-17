import { Head, Link, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Plus, Target, TrendingUp } from 'lucide-react';

export default function OKRsShow({ okr }) {
    const keyResultForm = useForm({
        title: '',
        description: '',
        type: 'number',
        target_value: '',
        current_value: '',
        unit: '',
    });

    const updateKeyResultForm = useForm({
        current_value: '',
    });

    const handleAddKeyResult = (e) => {
        e.preventDefault();
        keyResultForm.post(`/decisions/okrs/${okr.id}/key-results`, {
            preserveScroll: true,
            onSuccess: () => {
                keyResultForm.reset();
            },
        });
    };

    const handleUpdateKeyResult = (keyResultId, currentValue) => {
        updateKeyResultForm.put(`/decisions/okrs/${okr.id}/key-results/${keyResultId}`, {
            data: { current_value: currentValue },
            preserveScroll: true,
        });
    };

    const getStatusBadge = (status) => {
        const badges = {
            not_started: 'bg-gray-100 text-gray-700',
            in_progress: 'bg-blue-100 text-blue-700',
            completed: 'bg-green-100 text-green-700',
            at_risk: 'bg-red-100 text-red-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title={okr.title} />
            <div className="max-w-5xl mx-auto ">
                <Link href="/decisions/okrs">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to OKRs
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{okr.title}</h1>
                            {okr.description && (
                                <p className="text-gray-500 mt-2">{okr.description}</p>
                            )}
                        </div>
                        <span className={`px-3 py-1 text-sm font-medium rounded-full ${
                            okr.status === 'active' ? 'bg-green-100 text-green-700' :
                            okr.status === 'completed' ? 'bg-blue-100 text-blue-700' :
                            okr.status === 'cancelled' ? 'bg-red-100 text-red-700' :
                            'bg-gray-100 text-gray-700'
                        }`}>
                            {okr.status.charAt(0).toUpperCase() + okr.status.slice(1)}
                        </span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <div className="text-sm text-gray-600 mb-1">Quarter</div>
                            <div className="font-medium text-gray-900">{okr.quarter}</div>
                        </div>
                        <div>
                            <div className="text-sm text-gray-600 mb-1">Progress</div>
                            <div className="flex items-center gap-2">
                                <div className="flex-1 bg-gray-200 rounded-full h-3">
                                    <div
                                        className="bg-teal-500 h-3 rounded-full"
                                        style={{ width: `${okr.progress_percentage}%` }}
                                    />
                                </div>
                                <span className="font-medium text-gray-900">{okr.progress_percentage}%</span>
                            </div>
                        </div>
                        <div>
                            <div className="text-sm text-gray-600 mb-1">Date Range</div>
                            <div className="font-medium text-gray-900">
                                {new Date(okr.start_date).toLocaleDateString()} - {new Date(okr.end_date).toLocaleDateString()}
                            </div>
                        </div>
                    </div>

                    {/* Key Results */}
                    <div className="border-t border-gray-200 pt-6">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-semibold text-gray-900">Key Results</h2>
                            <Button size="sm" onClick={() => document.getElementById('add-key-result-form').scrollIntoView()}>
                                <Plus className="h-4 w-4 mr-2" />
                                Add Key Result
                            </Button>
                        </div>

                        {okr.key_results && okr.key_results.length > 0 ? (
                            <div className="space-y-4">
                                {okr.key_results.map((kr) => (
                                    <div key={kr.id} className="border border-gray-200 rounded-lg p-4">
                                        <div className="flex items-start justify-between mb-3">
                                            <div className="flex-1">
                                                <h3 className="font-medium text-gray-900">{kr.title}</h3>
                                                {kr.description && (
                                                    <p className="text-sm text-gray-500 mt-1">{kr.description}</p>
                                                )}
                                            </div>
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusBadge(kr.status)}`}>
                                                {kr.status.replace('_', ' ')}
                                            </span>
                                        </div>
                                        <div className="mb-3">
                                            <div className="flex items-center justify-between mb-1">
                                                <span className="text-sm text-gray-600">
                                                    {kr.current_value} {kr.unit || ''} / {kr.target_value} {kr.unit || ''}
                                                </span>
                                                <span className="text-sm font-medium text-gray-900">{kr.progress_percentage}%</span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-teal-500 h-2 rounded-full"
                                                    style={{ width: `${Math.min(kr.progress_percentage, 100)}%` }}
                                                />
                                            </div>
                                        </div>
                                        <div className="flex gap-2">
                                            <input
                                                type="number"
                                                defaultValue={kr.current_value}
                                                onBlur={(e) => {
                                                    const newValue = parseFloat(e.target.value) || 0;
                                                    if (newValue !== kr.current_value) {
                                                        handleUpdateKeyResult(kr.id, newValue);
                                                    }
                                                }}
                                                className="flex-1 px-3 py-1 text-sm border border-gray-300 rounded-lg"
                                                placeholder="Update current value"
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500 text-center py-8">No key results yet. Add your first key result below.</p>
                        )}

                        {/* Add Key Result Form */}
                        <div id="add-key-result-form" className="mt-6 border-t border-gray-200 pt-6">
                            <h3 className="text-md font-semibold text-gray-900 mb-4">Add Key Result</h3>
                            <form onSubmit={handleAddKeyResult} className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Title <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={keyResultForm.data.title}
                                            onChange={(e) => keyResultForm.setData('title', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Type <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={keyResultForm.data.type}
                                            onChange={(e) => keyResultForm.setData('type', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                            required
                                        >
                                            <option value="number">Number</option>
                                            <option value="percentage">Percentage</option>
                                            <option value="currency">Currency</option>
                                            <option value="boolean">Boolean</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea
                                        value={keyResultForm.data.description}
                                        onChange={(e) => keyResultForm.setData('description', e.target.value)}
                                        rows={2}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                    />
                                </div>
                                <div className="grid grid-cols-3 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Target Value <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={keyResultForm.data.target_value}
                                            onChange={(e) => keyResultForm.setData('target_value', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">Current Value</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={keyResultForm.data.current_value}
                                            onChange={(e) => keyResultForm.setData('current_value', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">Unit</label>
                                        <input
                                            type="text"
                                            value={keyResultForm.data.unit}
                                            onChange={(e) => keyResultForm.setData('unit', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                            placeholder="e.g., customers, %"
                                        />
                                    </div>
                                </div>
                                <Button type="submit" disabled={keyResultForm.processing}>
                                    {keyResultForm.processing ? 'Adding...' : 'Add Key Result'}
                                </Button>
                            </form>
                        </div>
                    </div>
                </div>

                <div className="flex gap-3">
                    <Link href={`/decisions/okrs/${okr.id}/edit`}>
                        <Button>Edit OKR</Button>
                    </Link>
                </div>
            </div>
        </SectionLayout>
    );
}

