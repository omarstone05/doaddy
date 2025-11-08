import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function PayrollRunsCreate({ teamMembers }) {
    const { data, setData, post, processing, errors } = useForm({
        pay_period: '',
        start_date: '',
        end_date: '',
        team_member_ids: [],
        notes: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/payroll/runs');
    };

    const toggleTeamMember = (memberId) => {
        const ids = [...data.team_member_ids];
        const index = ids.indexOf(memberId);
        if (index > -1) {
            ids.splice(index, 1);
        } else {
            ids.push(memberId);
        }
        setData('team_member_ids', ids);
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Create Payroll Run" />
            <div className="max-w-4xl mx-auto">
                <Link href="/payroll/runs">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Payroll Runs
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <h1 className="text-2xl font-bold text-gray-900 mb-6">Create Payroll Run</h1>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Pay Period <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.pay_period}
                                onChange={(e) => setData('pay_period', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="e.g., 2024-01 for January 2024"
                                required
                            />
                            <p className="mt-1 text-sm text-gray-500">Format: YYYY-MM</p>
                            {errors.pay_period && <p className="mt-1 text-sm text-red-600">{errors.pay_period}</p>}
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
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Team Members <span className="text-red-500">*</span>
                            </label>
                            <div className="border border-gray-300 rounded-lg p-4 max-h-64 overflow-y-auto">
                                {teamMembers.length === 0 ? (
                                    <p className="text-sm text-gray-500">No active team members found.</p>
                                ) : (
                                    <div className="space-y-2">
                                        {teamMembers.map((member) => (
                                            <label key={member.id} className="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={data.team_member_ids.includes(member.id)}
                                                    onChange={() => toggleTeamMember(member.id)}
                                                    className="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                                                />
                                                <span className="ml-3 text-sm text-gray-900">
                                                    {member.first_name} {member.last_name}
                                                    {member.salary && (
                                                        <span className="text-gray-500 ml-2">
                                                            ({new Intl.NumberFormat('en-ZM', {
                                                                style: 'currency',
                                                                currency: 'ZMW',
                                                            }).format(member.salary)})
                                                        </span>
                                                    )}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                )}
                            </div>
                            {errors.team_member_ids && <p className="mt-1 text-sm text-red-600">{errors.team_member_ids}</p>}
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
                            <Link href="/payroll/runs">
                                <Button variant="secondary" type="button">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing || data.team_member_ids.length === 0}>
                                {processing ? 'Creating...' : 'Create Payroll Run'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </SectionLayout>
    );
}

