import { Head, Link, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Plus, Target, Calendar, CheckCircle } from 'lucide-react';

export default function StrategicGoalsShow({ goal }) {
    const milestoneForm = useForm({
        title: '',
        description: '',
        target_date: '',
    });

    const handleAddMilestone = (e) => {
        e.preventDefault();
        milestoneForm.post(`/decisions/goals/${goal.id}/milestones`, {
            preserveScroll: true,
            onSuccess: () => {
                milestoneForm.reset();
            },
        });
    };

    const getStatusBadge = (status) => {
        const badges = {
            pending: 'bg-gray-100 text-gray-700',
            in_progress: 'bg-blue-100 text-blue-700',
            completed: 'bg-green-100 text-green-700',
            overdue: 'bg-red-100 text-red-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title={goal.title} />
            <div className="max-w-5xl mx-auto ">
                <Link href="/decisions/goals">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Strategic Goals
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{goal.title}</h1>
                            {goal.description && (
                                <p className="text-gray-500 mt-2">{goal.description}</p>
                            )}
                        </div>
                        <span className={`px-3 py-1 text-sm font-medium rounded-full ${
                            goal.status === 'active' ? 'bg-green-100 text-green-700' :
                            goal.status === 'completed' ? 'bg-blue-100 text-blue-700' :
                            goal.status === 'cancelled' ? 'bg-red-100 text-red-700' :
                            'bg-gray-100 text-gray-700'
                        }`}>
                            {goal.status.charAt(0).toUpperCase() + goal.status.slice(1)}
                        </span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <div className="text-sm text-gray-600 mb-1">Target Date</div>
                            <div className="font-medium text-gray-900">{new Date(goal.target_date).toLocaleDateString()}</div>
                        </div>
                        <div>
                            <div className="text-sm text-gray-600 mb-1">Progress</div>
                            <div className="flex items-center gap-2">
                                <div className="flex-1 bg-gray-200 rounded-full h-3">
                                    <div
                                        className="bg-teal-500 h-3 rounded-full"
                                        style={{ width: `${goal.progress_percentage}%` }}
                                    />
                                </div>
                                <span className="font-medium text-gray-900">{goal.progress_percentage}%</span>
                            </div>
                        </div>
                        <div>
                            <div className="text-sm text-gray-600 mb-1">Milestones</div>
                            <div className="font-medium text-gray-900">
                                {goal.milestones?.filter(m => m.status === 'completed').length || 0} / {goal.milestones?.length || 0} completed
                            </div>
                        </div>
                    </div>

                    {/* Milestones */}
                    <div className="border-t border-gray-200 pt-6">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-semibold text-gray-900">Milestones</h2>
                            <Button size="sm" onClick={() => document.getElementById('add-milestone-form').scrollIntoView()}>
                                <Plus className="h-4 w-4 mr-2" />
                                Add Milestone
                            </Button>
                        </div>

                        {goal.milestones && goal.milestones.length > 0 ? (
                            <div className="space-y-3">
                                {goal.milestones.map((milestone) => (
                                    <div key={milestone.id} className="border border-gray-200 rounded-lg p-4">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <CheckCircle className={`h-4 w-4 ${
                                                        milestone.status === 'completed' ? 'text-green-500' : 'text-gray-400'
                                                    }`} />
                                                    <h3 className="font-medium text-gray-900">{milestone.title}</h3>
                                                </div>
                                                {milestone.description && (
                                                    <p className="text-sm text-gray-500 mt-1 ml-6">{milestone.description}</p>
                                                )}
                                                <div className="text-sm text-gray-500 mt-1 ml-6">
                                                    Target: {new Date(milestone.target_date).toLocaleDateString()}
                                                    {milestone.completed_date && (
                                                        <span className="ml-2 text-green-600">
                                                            Completed: {new Date(milestone.completed_date).toLocaleDateString()}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusBadge(milestone.status)}`}>
                                                {milestone.status.replace('_', ' ')}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500 text-center py-8">No milestones yet. Add your first milestone below.</p>
                        )}

                        {/* Add Milestone Form */}
                        <div id="add-milestone-form" className="mt-6 border-t border-gray-200 pt-6">
                            <h3 className="text-md font-semibold text-gray-900 mb-4">Add Milestone</h3>
                            <form onSubmit={handleAddMilestone} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Title <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={milestoneForm.data.title}
                                        onChange={(e) => milestoneForm.setData('title', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea
                                        value={milestoneForm.data.description}
                                        onChange={(e) => milestoneForm.setData('description', e.target.value)}
                                        rows={2}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Target Date <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        value={milestoneForm.data.target_date}
                                        onChange={(e) => milestoneForm.setData('target_date', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                        required
                                    />
                                </div>
                                <Button type="submit" disabled={milestoneForm.processing}>
                                    {milestoneForm.processing ? 'Adding...' : 'Add Milestone'}
                                </Button>
                            </form>
                        </div>
                    </div>
                </div>

                <div className="flex gap-3">
                    <Link href={`/decisions/goals/${goal.id}/edit`}>
                        <Button>Edit Goal</Button>
                    </Link>
                </div>
            </div>
        </SectionLayout>
    );
}

