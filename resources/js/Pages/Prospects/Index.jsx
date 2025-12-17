import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Edit, Eye, Search, Users } from 'lucide-react';
import { useState } from 'react';

export default function ProspectsIndex({ prospects, filters }) {
    const [searchQuery, setSearchQuery] = useState(filters?.search || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.visit('/prospects?search=' + encodeURIComponent(searchQuery));
    };

    const getStageColor = (stage) => {
        const colors = {
            lead: 'bg-gray-100 text-gray-700',
            contacted: 'bg-blue-100 text-blue-700',
            qualified: 'bg-yellow-100 text-yellow-700',
            proposal: 'bg-purple-100 text-purple-700',
            negotiation: 'bg-orange-100 text-orange-700',
            won: 'bg-green-100 text-green-700',
            lost: 'bg-red-100 text-red-700',
        };
        return colors[stage] || colors.lead;
    };

    return (
        <SectionLayout sectionName="Sales">
            <Head title="Prospects" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Prospects</h1>
                        <p className="text-gray-500 mt-1">Manage your sales pipeline</p>
                    </div>
                    <Button onClick={() => router.visit('/prospects/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        Add Prospect
                    </Button>
                </div>

                {/* Search */}
                <form onSubmit={handleSearch} className="mb-6">
                    <div className="flex gap-2">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Search prospects by name, company, email..."
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                        <Button type="submit" variant="secondary">
                            Search
                        </Button>
                    </div>
                </form>

                {prospects.data.length === 0 ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                        <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No prospects yet</h3>
                        <p className="text-gray-500 mb-4">Create your first prospect to start building your pipeline</p>
                        <Button onClick={() => router.visit('/prospects/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            Create Prospect
                        </Button>
                    </div>
                ) : (
                    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Name / Company
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Stage
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Value
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {prospects.data.map((prospect) => (
                                    <tr key={prospect.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="font-medium text-gray-900">{prospect.name}</div>
                                            {prospect.company_name && (
                                                <div className="text-sm text-gray-500">{prospect.company_name}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-sm text-gray-900">{prospect.email || '-'}</div>
                                            <div className="text-sm text-gray-500">{prospect.phone || '-'}</div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStageColor(prospect.stage)}`}>
                                                {prospect.stage}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {prospect.estimated_value ? `$${prospect.estimated_value.toLocaleString()}` : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <Link
                                                    href={`/prospects/${prospect.id}`}
                                                    className="text-teal-500 hover:text-teal-600"
                                                    title="View"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={`/prospects/${prospect.id}/edit`}
                                                    className="text-gray-400 hover:text-gray-600"
                                                    title="Edit"
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
                        {prospects.links && prospects.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                <div className="text-sm text-gray-500">
                                    Showing {prospects.from} to {prospects.to} of {prospects.total} results
                                </div>
                                <div className="flex gap-2">
                                    {prospects.links.map((link, index) => (
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

