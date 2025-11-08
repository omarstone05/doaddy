import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, Edit, Trash2, Percent, DollarSign } from 'lucide-react';

export default function CommissionRulesIndex({ commissionRules, filters }) {
    const handleDelete = (ruleId) => {
        if (confirm('Are you sure you want to delete this commission rule?')) {
            router.delete(`/commissions/rules/${ruleId}`);
        }
    };

    const getRuleTypeBadge = (ruleType) => {
        const badges = {
            percentage: 'bg-blue-100 text-blue-700',
            fixed: 'bg-green-100 text-green-700',
            tiered: 'bg-purple-100 text-purple-700',
        };
        return badges[ruleType] || 'bg-gray-100 text-gray-700';
    };

    const formatRuleDisplay = (rule) => {
          if (rule.rule_type === 'percentage') {
              return `${rule.rate}%`;
          } else if (rule.rule_type === 'fixed') {
            return new Intl.NumberFormat('en-ZM', {
                style: 'currency',
                currency: 'ZMW',
            }).format(rule.fixed_amount);
        } else {
            return 'Tiered';
        }
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Commission Rules" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Commission Rules</h1>
                        <p className="text-gray-500 mt-1">Manage commission rules for your team</p>
                    </div>
                    <Button onClick={() => router.visit('/commissions/rules/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Commission Rule
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.is_active || ''}
                                onChange={(e) => router.visit(`/commissions/rules?is_active=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Status</option>
                                <option value="true">Active</option>
                                <option value="false">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Applicable To</label>
                            <select
                                value={filters?.applicable_to || ''}
                                onChange={(e) => router.visit(`/commissions/rules?applicable_to=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All</option>
                                <option value="all">All Team Members</option>
                                <option value="team_member">Specific Team Member</option>
                                <option value="department">Department</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Commission Rules Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Type</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Rate/Amount</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Applicable To</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {commissionRules.data.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                                        No commission rules found. Create your first commission rule to get started.
                                    </td>
                                </tr>
                            ) : (
                                commissionRules.data.map((rule) => (
                                    <tr key={rule.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">{rule.name}</div>
                                            {rule.description && (
                                                <div className="text-sm text-gray-500 mt-1">{rule.description}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getRuleTypeBadge(rule.rule_type)}`}>
                                                {rule.rule_type.charAt(0).toUpperCase() + rule.rule_type.slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {formatRuleDisplay(rule)}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {rule.applicable_to === 'all' && 'All Team Members'}
                                            {rule.applicable_to === 'team_member' && rule.team_member && (
                                                <span>{rule.team_member.first_name} {rule.team_member.last_name}</span>
                                            )}
                                            {rule.applicable_to === 'department' && rule.department && (
                                                <span>{rule.department.name}</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                rule.is_active
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-gray-100 text-gray-700'
                                            }`}>
                                                {rule.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={`/commissions/rules/${rule.id}/edit`}>
                                                    <button className="text-gray-400 hover:text-teal-600">
                                                        <Edit className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(rule.id)}
                                                    className="text-gray-400 hover:text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {commissionRules.links && commissionRules.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {commissionRules.from} to {commissionRules.to} of {commissionRules.total} results
                        </div>
                        <div className="flex gap-2">
                            {commissionRules.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.visit(link.url)}
                                    disabled={!link.url}
                                    className={`px-3 py-2 text-sm border rounded-lg ${
                                        link.active
                                            ? 'bg-teal-500 text-white border-teal-500'
                                            : link.url
                                            ? 'border-gray-300 hover:bg-gray-50'
                                            : 'border-gray-200 text-gray-400 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

