import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Link, router } from '@inertiajs/react';
import { Ticket, Search, Eye, AlertCircle } from 'lucide-react';
import { useState } from 'react';

export default function Index({ tickets, filters }) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/tickets', { search }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getStatusBadge = (status) => {
        const styles = {
            open: 'bg-blue-100 text-blue-800',
            in_progress: 'bg-yellow-100 text-yellow-800',
            waiting_customer: 'bg-orange-100 text-orange-800',
            resolved: 'bg-green-100 text-green-800',
            closed: 'bg-gray-100 text-gray-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-medium rounded-full ${styles[status] || styles.open}`}>
                {status.replace('_', ' ')}
            </span>
        );
    };

    const getPriorityBadge = (priority) => {
        const styles = {
            urgent: 'bg-red-100 text-red-800',
            high: 'bg-orange-100 text-orange-800',
            medium: 'bg-yellow-100 text-yellow-800',
            low: 'bg-gray-100 text-gray-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-medium rounded-full ${styles[priority] || styles.medium}`}>
                {priority}
            </span>
        );
    };

    return (
        <AdminLayout title="Support Tickets">
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Support Tickets</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Manage and respond to support tickets
                    </p>
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
                                placeholder="Search tickets..."
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
                                        Ticket
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Priority
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Assigned To
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
                                {tickets.data.map((ticket) => (
                                    <tr key={ticket.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {ticket.ticket_number}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {ticket.subject}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {ticket.user?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getStatusBadge(ticket.status)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getPriorityBadge(ticket.priority)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {ticket.assigned_to_user?.name || 'Unassigned'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {new Date(ticket.created_at).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link
                                                href={`/admin/tickets/${ticket.id}`}
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
                    {tickets.links && tickets.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-700">
                                    Showing {tickets.from} to {tickets.to} of {tickets.total} results
                                </div>
                                <div className="flex space-x-2">
                                    {tickets.links.map((link, index) => (
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
            </div>
        </AdminLayout>
    );
}

