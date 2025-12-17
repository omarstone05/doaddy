import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Link, router } from '@inertiajs/react';
import { Ticket, User, MessageSquare, Send } from 'lucide-react';
import { useState } from 'react';

export default function Show({ ticket }) {
    const [message, setMessage] = useState('');
    const [isInternal, setIsInternal] = useState(false);
    const [status, setStatus] = useState(ticket.status);

    const handleSendMessage = (e) => {
        e.preventDefault();
        router.post(`/admin/tickets/${ticket.id}/messages`, {
            message,
            is_internal_note: isInternal,
        }, {
            preserveScroll: true,
            onSuccess: () => setMessage(''),
        });
    };

    const handleStatusChange = (newStatus) => {
        router.post(`/admin/tickets/${ticket.id}/status`, {
            status: newStatus,
        }, {
            preserveScroll: true,
            onSuccess: () => setStatus(newStatus),
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
            <span className={`px-3 py-1 text-sm font-medium rounded-full ${styles[status] || styles.open}`}>
                {status.replace('_', ' ')}
            </span>
        );
    };

    return (
        <AdminLayout title={`Ticket ${ticket.ticket_number}`}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href="/admin/tickets"
                            className="text-gray-500 hover:text-gray-700"
                        >
                            ← Back
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                {ticket.ticket_number}
                            </h1>
                            <p className="mt-1 text-sm text-gray-500">{ticket.subject}</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        {getStatusBadge(status)}
                        <select
                            value={status}
                            onChange={(e) => handleStatusChange(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                        >
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="waiting_customer">Waiting Customer</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>

                {/* Ticket Details */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                        {/* Description */}
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Description
                            </h3>
                            <p className="text-sm text-gray-700 whitespace-pre-wrap">
                                {ticket.description}
                            </p>
                        </Card>

                        {/* Messages */}
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Messages
                            </h3>
                            <div className="space-y-4">
                                {ticket.messages && ticket.messages.length > 0 ? (
                                    ticket.messages.map((msg) => (
                                        <div
                                            key={msg.id}
                                            className={`p-4 rounded-lg ${
                                                msg.is_internal_note
                                                    ? 'bg-yellow-50 border border-yellow-200'
                                                    : 'bg-gray-50'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between mb-2">
                                                <div className="flex items-center space-x-2">
                                                    <User className="w-4 h-4 text-gray-400" />
                                                    <span className="text-sm font-medium text-gray-900">
                                                        {msg.user?.name || 'Unknown'}
                                                    </span>
                                                    {msg.is_internal_note && (
                                                        <span className="px-2 py-1 text-xs bg-yellow-200 text-yellow-800 rounded">
                                                            Internal
                                                        </span>
                                                    )}
                                                </div>
                                                <span className="text-xs text-gray-500">
                                                    {new Date(msg.created_at).toLocaleString()}
                                                </span>
                                            </div>
                                            <p className="text-sm text-gray-700 whitespace-pre-wrap">
                                                {msg.message}
                                            </p>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-gray-500">No messages yet</p>
                                )}
                            </div>
                        </Card>

                        {/* Reply Form */}
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Add Message
                            </h3>
                            <form onSubmit={handleSendMessage} className="space-y-4">
                                <div>
                                    <textarea
                                        value={message}
                                        onChange={(e) => setMessage(e.target.value)}
                                        rows={4}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        placeholder="Type your message..."
                                        required
                                    />
                                </div>
                                <div className="flex items-center space-x-4">
                                    <label className="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            checked={isInternal}
                                            onChange={(e) => setIsInternal(e.target.checked)}
                                            className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                        />
                                        <span className="text-sm text-gray-700">
                                            Internal note (not visible to customer)
                                        </span>
                                    </label>
                                    <Button type="submit" className="ml-auto">
                                        <Send className="w-4 h-4 mr-2" />
                                        Send Message
                                    </Button>
                                </div>
                            </form>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Ticket Info
                            </h3>
                            <dl className="space-y-3">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Status</dt>
                                    <dd className="mt-1">{getStatusBadge(status)}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Priority</dt>
                                    <dd className="mt-1 text-sm text-gray-900 capitalize">
                                        {ticket.priority}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Category</dt>
                                    <dd className="mt-1 text-sm text-gray-900 capitalize">
                                        {ticket.category}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Created</dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {new Date(ticket.created_at).toLocaleString()}
                                    </dd>
                                </div>
                            </dl>
                        </Card>

                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">User</h3>
                            <div className="space-y-2">
                                <p className="text-sm font-medium text-gray-900">
                                    {ticket.user?.name || 'N/A'}
                                </p>
                                <p className="text-sm text-gray-500">{ticket.user?.email || 'N/A'}</p>
                            </div>
                        </Card>

                        {ticket.organization && (
                            <Card className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                    Organization
                                </h3>
                                <p className="text-sm text-gray-900">
                                    {ticket.organization.name}
                                </p>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

