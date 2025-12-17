import { useState, useEffect } from 'react';
import { X, Plus, Edit, Trash2, DollarSign } from 'lucide-react';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import axios from 'axios';
import { router } from '@inertiajs/react';

export default function CommissionRulesModal({ isOpen, onClose, onRuleCreated }) {
    const [commissionRules, setCommissionRules] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        rule_type: 'percentage',
        rate: '',
        fixed_amount: '',
        applicable_to: 'all',
        team_member_id: '',
        department_id: '',
        is_active: true,
    });
    const [teamMembers, setTeamMembers] = useState([]);
    const [departments, setDepartments] = useState([]);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (isOpen) {
            loadCommissionRules();
            loadTeamMembers();
            loadDepartments();
        }
    }, [isOpen]);

    const loadCommissionRules = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/commissions/rules');
            setCommissionRules(response.data.commissionRules?.data || []);
        } catch (error) {
            console.error('Failed to load commission rules:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadTeamMembers = async () => {
        try {
            // Use the same endpoint that the create form uses
            const response = await axios.get('/commissions/rules/create');
            setTeamMembers(response.data.teamMembers || []);
        } catch (error) {
            console.error('Failed to load team members:', error);
        }
    };

    const loadDepartments = async () => {
        try {
            // Use the same endpoint that the create form uses
            const response = await axios.get('/commissions/rules/create');
            setDepartments(response.data.departments || []);
        } catch (error) {
            console.error('Failed to load departments:', error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await axios.post('/commissions/rules', formData);
            setShowCreateForm(false);
            setFormData({
                name: '',
                description: '',
                rule_type: 'percentage',
                rate: '',
                fixed_amount: '',
                applicable_to: 'all',
                team_member_id: '',
                department_id: '',
                is_active: true,
            });
            loadCommissionRules();
            if (onRuleCreated) onRuleCreated();
        } catch (error) {
            console.error('Failed to create commission rule:', error);
            alert('Failed to create commission rule. Please try again.');
        } finally {
            setSubmitting(false);
        }
    };

    const handleDelete = async (ruleId) => {
        if (!confirm('Are you sure you want to delete this commission rule?')) return;
        try {
            await axios.delete(`/commissions/rules/${ruleId}`);
            loadCommissionRules();
        } catch (error) {
            console.error('Failed to delete commission rule:', error);
            alert('Failed to delete commission rule. Please try again.');
        }
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

    const getRuleTypeBadge = (ruleType) => {
        const badges = {
            percentage: 'bg-blue-100 text-blue-700',
            fixed: 'bg-green-100 text-green-700',
            tiered: 'bg-purple-100 text-purple-700',
        };
        return badges[ruleType] || 'bg-gray-100 text-gray-700';
    };

    if (!isOpen) return null;

    return (
        <>
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-gradient-to-br from-teal-50/30 via-mint-50/20 to-white/40 backdrop-blur-md z-50" 
                onClick={onClose} 
            />
            
            {/* Modal */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div className="bg-white/90 backdrop-blur-2xl rounded-3xl shadow-2xl border border-white/60 max-w-4xl w-full max-h-[90vh] overflow-y-auto relative" style={{
                    background: 'linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(240,253,250,0.9) 50%, rgba(255,255,255,0.95) 100%)'
                }}>
                    {/* Header */}
                    <div className="sticky top-0 bg-white/40 backdrop-blur-md border-b border-mint-200/40 px-6 py-4 flex items-center justify-between z-10 rounded-t-3xl" style={{
                        background: 'linear-gradient(180deg, rgba(255,255,255,0.6) 0%, rgba(240,253,250,0.4) 100%)'
                    }}>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm shadow-md flex items-center justify-center border border-mint-200/30">
                                <DollarSign className="h-5 w-5 text-teal-500" />
                            </div>
                            <div>
                                <h3 className="text-xl font-bold text-teal-700">Commission Rules</h3>
                                <p className="text-xs text-teal-600/70">Manage commission rules</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            {!showCreateForm && (
                                <Button
                                    onClick={() => setShowCreateForm(true)}
                                    size="sm"
                                    className="bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700"
                                >
                                    <Plus className="h-4 w-4 mr-2" />
                                    New Rule
                                </Button>
                            )}
                            <button
                                onClick={onClose}
                                className="p-2 rounded-xl bg-white/60 hover:bg-white/80 backdrop-blur-sm border border-mint-200/50 text-teal-700 hover:text-teal-800 transition-all shadow-sm"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="p-6">
                        {showCreateForm ? (
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Name <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        rows={2}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Rule Type <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={formData.rule_type}
                                        onChange={(e) => setFormData({ ...formData, rule_type: e.target.value })}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="percentage">Percentage</option>
                                        <option value="fixed">Fixed Amount</option>
                                        <option value="tiered">Tiered</option>
                                    </select>
                                </div>

                                {formData.rule_type === 'percentage' && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Percentage Rate <span className="text-red-500">*</span>
                                        </label>
                                        <div className="relative">
                                            <input
                                                type="number"
                                                value={formData.rate}
                                                onChange={(e) => setFormData({ ...formData, rate: e.target.value })}
                                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                required
                                            />
                                            <span className="absolute right-4 top-2 text-gray-500">%</span>
                                        </div>
                                    </div>
                                )}

                                {formData.rule_type === 'fixed' && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Fixed Amount <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            value={formData.fixed_amount}
                                            onChange={(e) => setFormData({ ...formData, fixed_amount: e.target.value })}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                            min="0"
                                            step="0.01"
                                            required
                                        />
                                    </div>
                                )}

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Applicable To <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={formData.applicable_to}
                                        onChange={(e) => setFormData({ ...formData, applicable_to: e.target.value })}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="all">All Team Members</option>
                                        <option value="team_member">Specific Team Member</option>
                                        <option value="department">Department</option>
                                    </select>
                                </div>

                                {formData.applicable_to === 'team_member' && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Team Member <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={formData.team_member_id}
                                            onChange={(e) => setFormData({ ...formData, team_member_id: e.target.value })}
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
                                    </div>
                                )}

                                {formData.applicable_to === 'department' && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Department <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={formData.department_id}
                                            onChange={(e) => setFormData({ ...formData, department_id: e.target.value })}
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
                                    </div>
                                )}

                                <div className="flex items-start">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        checked={formData.is_active}
                                        onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                        className="mt-1 h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="is_active" className="ml-3 text-sm font-medium text-gray-700">
                                        Active
                                    </label>
                                </div>

                                <div className="flex gap-3 pt-4 border-t border-mint-200/40">
                                    <Button
                                        variant="outline"
                                        type="button"
                                        onClick={() => {
                                            setShowCreateForm(false);
                                            setFormData({
                                                name: '',
                                                description: '',
                                                rule_type: 'percentage',
                                                rate: '',
                                                fixed_amount: '',
                                                applicable_to: 'all',
                                                team_member_id: '',
                                                department_id: '',
                                                is_active: true,
                                            });
                                        }}
                                        className="flex-1 bg-white/60 backdrop-blur-sm border-mint-200/50"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={submitting}
                                        className="flex-1 bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700"
                                    >
                                        {submitting ? 'Creating...' : 'Create Rule'}
                                    </Button>
                                </div>
                            </form>
                        ) : (
                            <>
                                {loading ? (
                                    <div className="text-center py-12">
                                        <p className="text-gray-500">Loading commission rules...</p>
                                    </div>
                                ) : commissionRules.length === 0 ? (
                                    <div className="text-center py-12">
                                        <p className="text-gray-500 mb-4">No commission rules found.</p>
                                        <Button
                                            onClick={() => setShowCreateForm(true)}
                                            className="bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700"
                                        >
                                            <Plus className="h-4 w-4 mr-2" />
                                            Create First Rule
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {commissionRules.map((rule) => (
                                            <Card key={rule.id} className="p-4 bg-white/60 backdrop-blur-sm border border-mint-200/50">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-3 mb-2">
                                                            <h4 className="font-semibold text-gray-900">{rule.name}</h4>
                                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getRuleTypeBadge(rule.rule_type)}`}>
                                                                {rule.rule_type.charAt(0).toUpperCase() + rule.rule_type.slice(1)}
                                                            </span>
                                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                                rule.is_active
                                                                    ? 'bg-green-100 text-green-700'
                                                                    : 'bg-gray-100 text-gray-700'
                                                            }`}>
                                                                {rule.is_active ? 'Active' : 'Inactive'}
                                                            </span>
                                                        </div>
                                                        {rule.description && (
                                                            <p className="text-sm text-gray-600 mb-2">{rule.description}</p>
                                                        )}
                                                        <div className="flex items-center gap-4 text-sm text-gray-600">
                                                            <span>Rate: {formatRuleDisplay(rule)}</span>
                                                            <span>•</span>
                                                            <span>
                                                                {rule.applicable_to === 'all' && 'All Team Members'}
                                                                {rule.applicable_to === 'team_member' && rule.team_member && (
                                                                    `${rule.team_member.first_name} ${rule.team_member.last_name}`
                                                                )}
                                                                {rule.applicable_to === 'department' && rule.department && (
                                                                    rule.department.name
                                                                )}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-2 ml-4">
                                                        <button
                                                            onClick={() => router.visit(`/commissions/rules/${rule.id}/edit`)}
                                                            className="p-2 text-gray-400 hover:text-teal-600 rounded-lg hover:bg-teal-50/50 transition-colors"
                                                            title="Edit"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => handleDelete(rule.id)}
                                                            className="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50/50 transition-colors"
                                                            title="Delete"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            </Card>
                                        ))}
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

