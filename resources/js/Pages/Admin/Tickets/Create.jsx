import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Link, router, Head, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

export default function Create({ users, organizations }) {
    const { data, setData, post, processing, errors } = useForm({
        subject: '',
        description: '',
        priority: 'medium',
        category: 'other',
        user_id: '',
        organization_id: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/tickets', {
            preserveScroll: true,
        });
    };

    // Filter users when organization is selected
    const filteredUsers = data.organization_id
        ? users.filter(user => 
            user.organizations && user.organizations.some(org => org.id === data.organization_id)
        )
        : users;

    return (
        <AdminLayout title="Create Support Ticket">
            <Head title="Create Support Ticket" />
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <Link
                        href="/admin/tickets"
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        Back to Tickets
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Create Support Ticket</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Create a support ticket on behalf of a user
                    </p>
                </div>

                {/* Form */}
                <Card className="p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Organization */}
                        <div>
                            <label htmlFor="organization_id" className="block text-sm font-medium text-gray-700 mb-2">
                                Organization <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="organization_id"
                                value={data.organization_id}
                                onChange={(e) => {
                                    setData('organization_id', e.target.value);
                                    setData('user_id', ''); // Reset user when org changes
                                }}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="">Select an organization</option>
                                {organizations.map((org) => (
                                    <option key={org.id} value={org.id}>
                                        {org.name}
                                    </option>
                                ))}
                            </select>
                            {errors.organization_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.organization_id}</p>
                            )}
                        </div>

                        {/* User */}
                        <div>
                            <label htmlFor="user_id" className="block text-sm font-medium text-gray-700 mb-2">
                                User <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="user_id"
                                value={data.user_id}
                                onChange={(e) => setData('user_id', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                                disabled={!data.organization_id}
                            >
                                <option value="">Select a user</option>
                                {filteredUsers.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name} ({user.email})
                                    </option>
                                ))}
                            </select>
                            {errors.user_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.user_id}</p>
                            )}
                            {!data.organization_id && (
                                <p className="mt-1 text-xs text-gray-500">
                                    Please select an organization first
                                </p>
                            )}
                        </div>

                        {/* Subject */}
                        <div>
                            <label htmlFor="subject" className="block text-sm font-medium text-gray-700 mb-2">
                                Subject <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="subject"
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="Brief description of the issue"
                                required
                            />
                            {errors.subject && (
                                <p className="mt-1 text-sm text-red-600">{errors.subject}</p>
                            )}
                        </div>

                        {/* Category */}
                        <div>
                            <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-2">
                                Category <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="category"
                                value={data.category}
                                onChange={(e) => setData('category', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="technical">Technical Issue</option>
                                <option value="billing">Billing Question</option>
                                <option value="feature_request">Feature Request</option>
                                <option value="bug">Bug Report</option>
                                <option value="other">Other</option>
                            </select>
                            {errors.category && (
                                <p className="mt-1 text-sm text-red-600">{errors.category}</p>
                            )}
                        </div>

                        {/* Priority */}
                        <div>
                            <label htmlFor="priority" className="block text-sm font-medium text-gray-700 mb-2">
                                Priority <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="priority"
                                value={data.priority}
                                onChange={(e) => setData('priority', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            {errors.priority && (
                                <p className="mt-1 text-sm text-red-600">{errors.priority}</p>
                            )}
                        </div>

                        {/* Description */}
                        <div>
                            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                Description <span className="text-red-500">*</span>
                            </label>
                            <textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={8}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="Please provide as much detail as possible about the issue..."
                                required
                                minLength={10}
                            />
                            {errors.description && (
                                <p className="mt-1 text-sm text-red-600">{errors.description}</p>
                            )}
                            <p className="mt-1 text-xs text-gray-500">
                                Minimum 10 characters required
                            </p>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                            <Link href="/admin/tickets">
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Ticket'}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AdminLayout>
    );
}

