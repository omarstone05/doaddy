import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Link, router, Head, useForm } from '@inertiajs/react';
import { Ticket, User, MessageSquare, Send, ArrowLeft } from 'lucide-react';

export default function Show({ ticket }) {
    const { data, setData, post, processing, reset } = useForm({
        message: '',
    });

    const handleSendMessage = (e) => {
        e.preventDefault();
        post(`/support/tickets/${ticket.id}/messages`, {
            preserveScroll: true,
            onSuccess: () => reset(),
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
        <AuthenticatedLayout>
            <Head title={`Ticket ${ticket.ticket_number}`} />
            <div className="px-6 py-8 max-w-6xl mx-auto">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <Link
                                href="/support/tickets"
                                className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                            >
                                <ArrowLeft className="w-4 h-4 mr-1" />
                                Back to Tickets
                            </Link>
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900">
                                    {ticket.ticket_number}
                                </h1>
                                <p className="mt-1 text-sm text-gray-500">{ticket.subject}</p>
                            </div>
                        </div>
                        {getStatusBadge(ticket.status)}
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
                                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <MessageSquare className="w-5 h-5 mr-2" />
                                    Messages
                                </h3>
                                <div className="space-y-4">
                                    {ticket.messages && ticket.messages.length > 0 ? (
                                        ticket.messages.map((msg) => {
                                            const isUserMessage = msg.user_id === ticket.user_id;
                                            return (
                                                <div
                                                    key={msg.id}
                                                    className={`p-4 rounded-lg ${
                                                        isUserMessage
                                                            ? 'bg-teal-50 border border-teal-200'
                                                            : 'bg-gray-50 border border-gray-200'
                                                    }`}
                                                >
                                                    <div className="flex items-center justify-between mb-2">
                                                        <div className="flex items-center space-x-2">
                                                            <User className="w-4 h-4 text-gray-400" />
                                                            <span className="text-sm font-medium text-gray-900">
                                                                {msg.user?.name || 'Unknown'}
                                                            </span>
                                                            {!isUserMessage && (
                                                                <span className="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                                                    Support
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
                                            );
                                        })
                                    ) : (
                                        <p className="text-sm text-gray-500">No messages yet</p>
                                    )}
                                </div>
                            </Card>

                            {/* Reply Form */}
                            {ticket.status !== 'closed' && (
                                <Card className="p-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Add a Reply
                                    </h3>
                                    <form onSubmit={handleSendMessage} className="space-y-4">
                                        <div>
                                            <textarea
                                                value={data.message}
                                                onChange={(e) => setData('message', e.target.value)}
                                                rows={4}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                placeholder="Type your message..."
                                                required
                                            />
                                        </div>
                                        <Button type="submit" disabled={processing}>
                                            <Send className="w-4 h-4 mr-2" />
                                            {processing ? 'Sending...' : 'Send Message'}
                                        </Button>
                                    </form>
                                </Card>
                            )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            <Card className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <Ticket className="w-5 h-5 mr-2" />
                                    Ticket Info
                                </h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Status</dt>
                                        <dd className="mt-1">{getStatusBadge(ticket.status)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Priority</dt>
                                        <dd className="mt-1">{getPriorityBadge(ticket.priority)}</dd>
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
                                    {ticket.assigned_to && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Assigned To</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {ticket.assigned_to_user?.name || 'N/A'}
                                            </dd>
                                        </div>
                                    )}
                                </dl>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

