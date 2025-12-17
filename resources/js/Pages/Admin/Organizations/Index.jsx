import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Link, router } from '@inertiajs/react';
import { Building2, Search, Filter, Eye, Plus, X } from 'lucide-react';
import { useState } from 'react';

export default function Index({ organizations, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        slug: '',
        business_type: '',
        industry: '',
        tone_preference: 'professional',
        currency: 'ZMW',
        timezone: 'Africa/Lusaka',
        status: 'trial',
        billing_plan: '',
        mrr: '',
        trial_ends_at: '',
    });
    const [errors, setErrors] = useState({});

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/organizations', { search }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getStatusBadge = (status) => {
        const styles = {
            active: 'bg-green-100 text-green-800',
            trial: 'bg-blue-100 text-blue-800',
            suspended: 'bg-red-100 text-red-800',
            cancelled: 'bg-gray-100 text-gray-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-medium rounded-full ${styles[status] || styles.trial}`}>
                {status}
            </span>
        );
    };

    const handleInputChange = (field, value) => {
        setFormData(prev => {
            const updated = { ...prev, [field]: value };
            // Auto-generate slug from name
            if (field === 'name' && !prev.slug) {
                updated.slug = value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
            }
            return updated;
        });
        // Clear error for this field
        if (errors[field]) {
            setErrors(prev => {
                const updated = { ...prev };
                delete updated[field];
                return updated;
            });
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setErrors({});
        
        router.post('/admin/organizations', formData, {
            preserveScroll: true,
            onSuccess: () => {
                setShowCreateModal(false);
                setFormData({
                    name: '',
                    slug: '',
                    business_type: '',
                    industry: '',
                    tone_preference: 'professional',
                    currency: 'ZMW',
                    timezone: 'Africa/Lusaka',
                    status: 'trial',
                    billing_plan: '',
                    mrr: '',
                    trial_ends_at: '',
                });
            },
            onError: (errors) => {
                setErrors(errors);
            },
        });
    };

    return (
        <AdminLayout title="Organizations">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Organizations</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Manage all organizations on the platform
                        </p>
                    </div>
                    <Button onClick={() => setShowCreateModal(true)}>
                        <Plus className="w-4 h-4 mr-2" />
                        Add Organization
                    </Button>
                </div>

                {/* Filters */}
                <Card className="p-4">
                    <form onSubmit={handleSearch} className="flex items-center space-x-4">
                        <div className="flex-1 relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search organizations..."
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                        <Button type="submit">Search</Button>
                    </form>
                </Card>

                {/* Table */}
                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Organization
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Users
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Plan
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {organizations.data.map((org) => (
                                    <tr key={org.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center">
                                                <Building2 className="w-5 h-5 text-gray-400 mr-3" />
                                                <div>
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {org.name}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {org.slug}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getStatusBadge(org.status)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {org.users_count}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {org.billing_plan || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {new Date(org.created_at).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link
                                                href={`/admin/organizations/${org.id}`}
                                                className="text-teal-600 hover:text-teal-900"
                                            >
                                                <Eye className="w-5 h-5 inline" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {organizations.links && organizations.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-700">
                                    Showing {organizations.from} to {organizations.to} of{' '}
                                    {organizations.total} results
                                </div>
                                <div className="flex space-x-2">
                                    {organizations.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm rounded-lg ${
                                                link.active
                                                    ? 'bg-teal-600 text-white'
                                                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                            } ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>

                {/* Create Organization Modal */}
                {showCreateModal && (
                    <>
                        <div 
                            className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50"
                            onClick={() => setShowCreateModal(false)}
                        />
                        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                                <div className="p-6">
                                    <div className="flex items-center justify-between mb-6">
                                        <h2 className="text-2xl font-bold text-gray-900">Create New Organization</h2>
                                        <button
                                            onClick={() => setShowCreateModal(false)}
                                            className="p-2 rounded-lg hover:bg-gray-100 transition"
                                        >
                                            <X className="w-5 h-5 text-gray-500" />
                                        </button>
                                    </div>

                                    <form onSubmit={handleSubmit} className="space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Organization Name *
                                                </label>
                                                <input
                                                    type="text"
                                                    value={formData.name}
                                                    onChange={(e) => handleInputChange('name', e.target.value)}
                                                    className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent ${
                                                        errors.name ? 'border-red-500' : 'border-gray-300'
                                                    }`}
                                                    required
                                                />
                                                {errors.name && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Slug *
                                                </label>
                                                <input
                                                    type="text"
                                                    value={formData.slug}
                                                    onChange={(e) => handleInputChange('slug', e.target.value)}
                                                    className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent ${
                                                        errors.slug ? 'border-red-500' : 'border-gray-300'
                                                    }`}
                                                    required
                                                />
                                                {errors.slug && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.slug}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Business Type
                                                </label>
                                                <select
                                                    value={formData.business_type}
                                                    onChange={(e) => handleInputChange('business_type', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                >
                                                    <option value="">Select...</option>
                                                    <option value="sole_proprietorship">Sole Proprietorship</option>
                                                    <option value="partnership">Partnership</option>
                                                    <option value="corporation">Corporation</option>
                                                    <option value="llc">LLC</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Industry
                                                </label>
                                                <input
                                                    type="text"
                                                    value={formData.industry}
                                                    onChange={(e) => handleInputChange('industry', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                    placeholder="e.g., Retail, Services, Technology"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Tone Preference
                                                </label>
                                                <select
                                                    value={formData.tone_preference}
                                                    onChange={(e) => handleInputChange('tone_preference', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                >
                                                    <option value="professional">Professional</option>
                                                    <option value="casual">Casual</option>
                                                    <option value="motivational">Motivational</option>
                                                    <option value="sassy">Sassy</option>
                                                    <option value="technical">Technical</option>
                                                    <option value="friendly">Friendly (legacy)</option>
                                                    <option value="conversational">Conversational (legacy)</option>
                                                    <option value="formal">Formal (legacy)</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Currency
                                                </label>
                                                <select
                                                    value={formData.currency}
                                                    onChange={(e) => handleInputChange('currency', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                >
                                                    <option value="ZMW">ZMW (Zambian Kwacha)</option>
                                                    <option value="USD">USD (US Dollar)</option>
                                                    <option value="EUR">EUR (Euro)</option>
                                                    <option value="GBP">GBP (British Pound)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Timezone
                                                </label>
                                                <input
                                                    type="text"
                                                    value={formData.timezone}
                                                    onChange={(e) => handleInputChange('timezone', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                    placeholder="e.g., Africa/Lusaka"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Status
                                                </label>
                                                <select
                                                    value={formData.status}
                                                    onChange={(e) => handleInputChange('status', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                >
                                                    <option value="trial">Trial</option>
                                                    <option value="active">Active</option>
                                                    <option value="suspended">Suspended</option>
                                                    <option value="cancelled">Cancelled</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Billing Plan
                                                </label>
                                                <select
                                                    value={formData.billing_plan}
                                                    onChange={(e) => handleInputChange('billing_plan', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                >
                                                    <option value="">None</option>
                                                    <option value="free">Free</option>
                                                    <option value="starter">Starter</option>
                                                    <option value="professional">Professional</option>
                                                    <option value="enterprise">Enterprise</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    MRR (Monthly Recurring Revenue)
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={formData.mrr}
                                                    onChange={(e) => handleInputChange('mrr', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Trial Ends At
                                            </label>
                                            <input
                                                type="date"
                                                value={formData.trial_ends_at}
                                                onChange={(e) => handleInputChange('trial_ends_at', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                            />
                                        </div>

                                        <div className="flex justify-end space-x-3 pt-4 border-t">
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                onClick={() => setShowCreateModal(false)}
                                            >
                                                Cancel
                                            </Button>
                                            <Button type="submit">
                                                Create Organization
                                            </Button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
