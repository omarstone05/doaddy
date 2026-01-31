import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Plus, Search, Eye, Calendar, Filter } from 'lucide-react';
import { useState } from 'react';

export default function JournalEntriesIndex({ journalEntries, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.status || '');

    const handleSearch = (e) => {
        e.preventDefault();
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (statusFilter) params.append('status', statusFilter);
        router.visit(`/accounting/journal-entries?${params.toString()}`);
    };

    const getStatusBadge = (status) => {
        const styles = {
            draft: 'bg-gray-100 text-gray-700',
            posted: 'bg-green-100 text-green-700',
            reversed: 'bg-red-100 text-red-700',
        };
        return (
            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${styles[status] || styles.draft}`}>
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    return (
        <SectionLayout sectionName="Accounting">
            <Head title="Journal Entries" />
            <div className="max-w-7xl mx-auto">
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Journal Entries</h1>
                        <p className="text-gray-500 mt-1">Record and manage accounting transactions</p>
                    </div>
                    <Button onClick={() => router.visit('/accounting/journal-entries/create')} className="gap-2">
                        <Plus className="h-4 w-4" />
                        New Entry
                    </Button>
                </div>

                {/* Filters */}
                <Card className="p-6 mb-6">
                    <div className="flex items-center gap-3 mb-4">
                        <Filter className="w-5 h-5 text-teal-600" />
                        <h3 className="text-sm font-bold text-gray-900">Filters</h3>
                    </div>
                    <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Search</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Search by entry number, description, or reference..."
                                    className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Status</label>
                            <select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="">All Statuses</option>
                                <option value="draft">Draft</option>
                                <option value="posted">Posted</option>
                                <option value="reversed">Reversed</option>
                            </select>
                        </div>
                    </form>
                </Card>

                {/* Journal Entries Table */}
                {journalEntries.data && journalEntries.data.length > 0 ? (
                    <Card className="p-6">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Entry Number</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Date</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Description</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Reference</th>
                                        <th className="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Status</th>
                                        <th className="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {journalEntries.data.map((entry) => (
                                        <tr key={entry.id} className="hover:bg-teal-50/30 transition-colors">
                                            <td className="px-4 py-3 text-sm font-mono text-gray-900">
                                                {entry.entry_number}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                {new Date(entry.entry_date).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3">
                                                <p className="text-sm font-semibold text-gray-900">{entry.description}</p>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {entry.reference || '-'}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {getStatusBadge(entry.status)}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Link
                                                    href={`/accounting/journal-entries/${entry.id}`}
                                                    className="p-2 rounded-lg text-teal-600 hover:bg-teal-100 transition-colors"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {journalEntries.links && journalEntries.links.length > 3 && (
                            <div className="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
                                <p className="text-sm text-gray-600">
                                    Showing {journalEntries.from} to {journalEntries.to} of {journalEntries.total} entries
                                </p>
                                <div className="flex gap-1">
                                    {journalEntries.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => link.url && router.visit(link.url)}
                                            disabled={!link.url}
                                            className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                                                link.active
                                                    ? 'bg-teal-500 text-white shadow-sm'
                                                    : link.url
                                                    ? 'bg-white border border-gray-200 text-gray-700 hover:bg-teal-50 hover:border-teal-200'
                                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </Card>
                ) : (
                    <Card className="p-12 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-teal-100 flex items-center justify-center mx-auto mb-4">
                            <Plus className="h-8 w-8 text-teal-500" />
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 mb-2">No journal entries found</h3>
                        <p className="text-gray-500 mb-6">Get started by creating your first journal entry</p>
                        <Button onClick={() => router.visit('/accounting/journal-entries/create')} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Create Entry
                        </Button>
                    </Card>
                )}
            </div>
        </SectionLayout>
    );
}

