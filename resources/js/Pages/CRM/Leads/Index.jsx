import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, Edit, UserPlus } from 'lucide-react';

export default function LeadsIndex({ leads, filters }) {
    const getStatusColor = (status) => {
        const colors = {
            new: 'bg-blue-100 text-blue-800',
            contacted: 'bg-yellow-100 text-yellow-800',
            qualified: 'bg-green-100 text-green-800',
            unqualified: 'bg-gray-100 text-gray-800',
            converted: 'bg-teal-100 text-teal-800',
            lost: 'bg-red-100 text-red-800',
            nurturing: 'bg-purple-100 text-purple-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getRatingColor = (rating) => {
        const colors = {
            hot: 'bg-red-100 text-red-800',
            warm: 'bg-orange-100 text-orange-800',
            cold: 'bg-blue-100 text-blue-800',
        };
        return colors[rating] || 'bg-gray-100 text-gray-800';
    };

    return (
        <SectionLayout sectionName="CRM">
            <Head title="Leads" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Leads</h1>
                        <p className="text-gray-500 mt-1">Manage and track your sales leads</p>
                    </div>
                    <Button onClick={() => router.visit('/crm/leads/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Lead
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={filters?.search || ''}
                                onChange={(e) => router.visit(`/crm/leads?search=${e.target.value}`)}
                                placeholder="Name, company, email..."
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.lead_status || ''}
                                onChange={(e) => router.visit(`/crm/leads?lead_status=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Statuses</option>
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="qualified">Qualified</option>
                                <option value="unqualified">Unqualified</option>
                                <option value="converted">Converted</option>
                                <option value="lost">Lost</option>
                                <option value="nurturing">Nurturing</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Source</label>
                            <select
                                value={filters?.lead_source || ''}
                                onChange={(e) => router.visit(`/crm/leads?lead_source=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Sources</option>
                                <option value="website">Website</option>
                                <option value="referral">Referral</option>
                                <option value="social_media">Social Media</option>
                                <option value="cold_call">Cold Call</option>
                                <option value="event">Event</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                            <select
                                value={filters?.rating || ''}
                                onChange={(e) => router.visit(`/crm/leads?rating=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Ratings</option>
                                <option value="hot">Hot</option>
                                <option value="warm">Warm</option>
                                <option value="cold">Cold</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Leads Table */}
                {leads.data.length === 0 ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                        <UserPlus className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No leads yet</h3>
                        <p className="text-gray-500 mb-4">Create your first lead to start tracking sales opportunities</p>
                        <Button onClick={() => router.visit('/crm/leads/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            Create Lead
                        </Button>
                    </div>
                ) : (
                    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Lead
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Company
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Source
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Rating
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Score
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {leads.data.map((lead) => (
                                    <tr key={lead.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">
                                                {lead.first_name} {lead.last_name}
                                            </div>
                                            {lead.job_title && (
                                                <div className="text-sm text-gray-500">{lead.job_title}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {lead.company_name || '-'}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-sm text-gray-900">{lead.email || '-'}</div>
                                            {lead.phone && (
                                                <div className="text-xs text-gray-500">{lead.phone}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600 capitalize">
                                            {lead.lead_source?.replace('_', ' ') || '-'}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(lead.lead_status)}`}>
                                                {lead.lead_status}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            {lead.rating ? (
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${getRatingColor(lead.rating)}`}>
                                                    {lead.rating}
                                                </span>
                                            ) : (
                                                <span className="text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {lead.lead_score || 0}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <Link
                                                    href={`/crm/leads/${lead.id}`}
                                                    className="text-teal-500 hover:text-teal-600"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={`/crm/leads/${lead.id}/edit`}
                                                    className="text-blue-500 hover:text-blue-600"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {leads.links && leads.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                <div className="text-sm text-gray-500">
                                    Showing {leads.from} to {leads.to} of {leads.total} results
                                </div>
                                <div className="flex gap-2">
                                    {leads.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-1 rounded-lg text-sm ${
                                                link.active
                                                    ? 'bg-teal-500 text-white'
                                                    : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}


