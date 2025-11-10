import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Link, router } from '@inertiajs/react';
import { Building2, Users, Ticket, AlertCircle } from 'lucide-react';
import { useState } from 'react';

export default function Show({ organization, stats }) {
    const [suspensionReason, setSuspensionReason] = useState('');

    const handleSuspend = () => {
        if (!suspensionReason.trim()) {
            alert('Please provide a reason for suspension');
            return;
        }
        router.post(`/admin/organizations/${organization.id}/suspend`, {
            reason: suspensionReason,
        });
    };

    const handleUnsuspend = () => {
        router.post(`/admin/organizations/${organization.id}/unsuspend`);
    };

    const getStatusBadge = (status) => {
        const styles = {
            active: 'bg-green-100 text-green-800',
            trial: 'bg-blue-100 text-blue-800',
            suspended: 'bg-red-100 text-red-800',
            cancelled: 'bg-gray-100 text-gray-800',
        };
        return (
            <span className={`px-3 py-1 text-sm font-medium rounded-full ${styles[status] || styles.trial}`}>
                {status}
            </span>
        );
    };

    return (
        <AdminLayout title={organization.name}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href="/admin/organizations"
                            className="text-gray-500 hover:text-gray-700"
                        >
                            ← Back
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                {organization.name}
                            </h1>
                            <p className="mt-1 text-sm text-gray-500">{organization.slug}</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        {getStatusBadge(organization.status)}
                        {organization.status === 'suspended' ? (
                            <Button onClick={handleUnsuspend} variant="outline">
                                Unsuspend
                            </Button>
                        ) : (
                            <Button onClick={handleSuspend} variant="destructive">
                                Suspend
                            </Button>
                        )}
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Users</p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">
                                    {stats.users_count}
                                </p>
                            </div>
                            <Users className="w-8 h-8 text-blue-500" />
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">
                                    Support Tickets
                                </p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">
                                    {stats.support_tickets}
                                </p>
                            </div>
                            <Ticket className="w-8 h-8 text-orange-500" />
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">
                                    Addy Insights
                                </p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">
                                    {stats.addy_insights}
                                </p>
                            </div>
                            <AlertCircle className="w-8 h-8 text-purple-500" />
                        </div>
                    </Card>
                </div>

                {/* Details */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            Organization Details
                        </h3>
                        <dl className="space-y-3">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Status</dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {getStatusBadge(organization.status)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Billing Plan</dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {organization.billing_plan || 'N/A'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">MRR</dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    ${organization.mrr || 0}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Created</dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {new Date(organization.created_at).toLocaleString()}
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    <Card className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Users</h3>
                        <div className="space-y-3">
                            {organization.users && organization.users.length > 0 ? (
                                organization.users.map((user) => (
                                    <div
                                        key={user.id}
                                        className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">
                                                {user.name}
                                            </p>
                                            <p className="text-xs text-gray-500">{user.email}</p>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-gray-500">No users found</p>
                            )}
                        </div>
                    </Card>
                </div>

                {/* Suspension Modal (simplified) */}
                {organization.status !== 'suspended' && (
                    <Card className="p-6 border-red-200 bg-red-50">
                        <h3 className="text-lg font-semibold text-red-900 mb-4">
                            Suspend Organization
                        </h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Reason for Suspension
                                </label>
                                <textarea
                                    value={suspensionReason}
                                    onChange={(e) => setSuspensionReason(e.target.value)}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                    placeholder="Enter reason for suspension..."
                                />
                            </div>
                            <Button onClick={handleSuspend} variant="destructive">
                                Confirm Suspension
                            </Button>
                        </div>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}

