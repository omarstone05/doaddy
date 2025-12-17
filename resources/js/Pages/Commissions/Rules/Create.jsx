import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function CommissionRulesCreate({ teamMembers, departments }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        rule_type: 'percentage',
        rate: '',
        fixed_amount: '',
        tiers: [],
        applicable_to: 'all',
        team_member_id: '',
        department_id: '',
        is_active: true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/commissions/rules');
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Create Commission Rule" />
            <div className="max-w-4xl mx-auto">
                <Link href="/commissions/rules">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Commission Rules
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <h1 className="text-2xl font-bold text-gray-900 mb-6">Create Commission Rule</h1>

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
                                placeholder="e.g., Sales Commission 10%"
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
                                placeholder="Describe this commission rule..."
                            />
                            {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Rule Type <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={data.rule_type}
                                onChange={(e) => setData('rule_type', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                                <option value="tiered">Tiered</option>
                            </select>
                            {errors.rule_type && <p className="mt-1 text-sm text-red-600">{errors.rule_type}</p>}
                        </div>

                        {data.rule_type === 'percentage' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Percentage Rate <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        value={data.rate}
                                        onChange={(e) => setData('rate', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        required
                                    />
                                    <span className="absolute right-4 top-2 text-gray-500">%</span>
                                </div>
                                {errors.rate && <p className="mt-1 text-sm text-red-600">{errors.rate}</p>}
                            </div>
                        )}

                        {data.rule_type === 'fixed' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Fixed Amount <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    value={data.fixed_amount}
                                    onChange={(e) => setData('fixed_amount', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    min="0"
                                    step="0.01"
                                    required
                                />
                                {errors.fixed_amount && <p className="mt-1 text-sm text-red-600">{errors.fixed_amount}</p>}
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Applicable To <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={data.applicable_to}
                                onChange={(e) => setData('applicable_to', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="all">All Team Members</option>
                                <option value="team_member">Specific Team Member</option>
                                <option value="department">Department</option>
                            </select>
                            {errors.applicable_to && <p className="mt-1 text-sm text-red-600">{errors.applicable_to}</p>}
                        </div>

                        {data.applicable_to === 'team_member' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Team Member <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.team_member_id}
                                    onChange={(e) => setData('team_member_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Select team member</option>
                                    {teamMembers.map((member) => (
                                        <option key={member.id} value={member.id}>
                                            {member.first_name} {member.last_name}
                                        </option>
                                    ))}
                                </select>
                                {errors.team_member_id && <p className="mt-1 text-sm text-red-600">{errors.team_member_id}</p>}
                            </div>
                        )}

                        {data.applicable_to === 'department' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Department <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.department_id}
                                    onChange={(e) => setData('department_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Select department</option>
                                    {departments.map((dept) => (
                                        <option key={dept.id} value={dept.id}>
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.department_id && <p className="mt-1 text-sm text-red-600">{errors.department_id}</p>}
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
                            <Link href="/commissions/rules">
                                <Button variant="secondary" type="button">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Commission Rule'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </SectionLayout>
    );
}

