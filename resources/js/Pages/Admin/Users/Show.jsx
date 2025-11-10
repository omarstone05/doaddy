import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Link, router } from '@inertiajs/react';
import { User, Building2, Shield, ShieldCheck } from 'lucide-react';

export default function Show({ user, stats }) {
    const handleToggleSuperAdmin = () => {
        if (confirm(`Are you sure you want to ${user.is_super_admin ? 'remove' : 'grant'} super admin access for this user?`)) {
            router.post(`/admin/users/${user.id}/toggle-super-admin`, {}, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout title={user.name}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href="/admin/users"
                            className="text-gray-500 hover:text-gray-700"
                        >
                            ← Back
                        </Link>
                        <div>
                            <div className="flex items-center space-x-2">
                                <h1 className="text-3xl font-bold text-gray-900">{user.name}</h1>
                                {user.is_super_admin && (
                                    <ShieldCheck className="w-6 h-6 text-teal-600" />
                                )}
                            </div>
                            <p className="mt-1 text-sm text-gray-500">{user.email}</p>
                        </div>
                    </div>
                    <Button
                        onClick={handleToggleSuperAdmin}
                        variant={user.is_super_admin ? "destructive" : "outline"}
                    >
                        {user.is_super_admin ? (
                            <>
                                <Shield className="w-4 h-4 mr-2" />
                                Remove Super Admin
                            </>
                        ) : (
                            <>
                                <ShieldCheck className="w-4 h-4 mr-2" />
                                Grant Super Admin
                            </>
                        )}
                    </Button>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">
                                    Organization
                                </p>
                                <p className="mt-2 text-2xl font-bold text-gray-900">
                                    {user.organization?.name || 'N/A'}
                                </p>
                            </div>
                            <Building2 className="w-8 h-8 text-blue-500" />
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">
                                    Super Admin Status
                                </p>
                                <p className="mt-2 text-2xl font-bold text-gray-900">
                                    {user.is_super_admin ? 'Yes' : 'No'}
                                </p>
                            </div>
                            {user.is_super_admin ? (
                                <ShieldCheck className="w-8 h-8 text-teal-500" />
                            ) : (
                                <Shield className="w-8 h-8 text-gray-400" />
                            )}
                        </div>
                    </Card>
                </div>

                {/* Details */}
                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">User Details</h3>
                    <dl className="space-y-3">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Email</dt>
                            <dd className="mt-1 text-sm text-gray-900">{user.email}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Organization</dt>
                            <dd className="mt-1 text-sm text-gray-900">
                                {user.organization?.name || 'N/A'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Created</dt>
                            <dd className="mt-1 text-sm text-gray-900">
                                {new Date(user.created_at).toLocaleString()}
                            </dd>
                        </div>
                        {user.admin_notes && (
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Admin Notes</dt>
                                <dd className="mt-1 text-sm text-gray-900">{user.admin_notes}</dd>
                            </div>
                        )}
                    </dl>
                </Card>
            </div>
        </AdminLayout>
    );
}

