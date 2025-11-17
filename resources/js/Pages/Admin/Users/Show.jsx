import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Link, router, usePage } from '@inertiajs/react';
import { User, Building2, Shield, ShieldCheck, Key, Mail, UserCog } from 'lucide-react';
import { useState } from 'react';

export default function Show({ user, stats, roles }) {
    const { flash } = usePage().props;
    const [showPasswordModal, setShowPasswordModal] = useState(false);
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleToggleSuperAdmin = () => {
        if (confirm(`Are you sure you want to ${user.is_super_admin ? 'remove' : 'grant'} super admin access for this user?`)) {
            router.post(`/admin/users/${user.id}/toggle-super-admin`, {}, {
                preserveScroll: true,
            });
        }
    };

    const handleSendPasswordReset = () => {
        if (confirm(`Send a password reset email to ${user.email}?`)) {
            router.post(`/admin/users/${user.id}/send-password-reset`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    setShowPasswordModal(false);
                },
            });
        }
    };

    const handleChangePassword = (e) => {
        e.preventDefault();
        
        if (password !== passwordConfirmation) {
            alert('Passwords do not match');
            return;
        }

        if (password.length < 8) {
            alert('Password must be at least 8 characters');
            return;
        }

        if (confirm(`Are you sure you want to change the password for ${user.email}?`)) {
            setProcessing(true);
            router.post(`/admin/users/${user.id}/change-password`, {
                password,
                password_confirmation: passwordConfirmation,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    setShowPasswordModal(false);
                    setPassword('');
                    setPasswordConfirmation('');
                    setProcessing(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            });
        }
    };

    const handleChangeRole = (orgId, newRoleSlug) => {
        if (confirm(`Change user's role in this organization?`)) {
            router.post(`/admin/users/${user.id}/change-organization-role`, {
                organization_id: orgId,
                role_slug: newRoleSlug,
            }, {
                preserveScroll: true,
            });
        }
    };

    const getRoleColor = (role) => {
        const colors = {
            owner: 'bg-teal-100 text-teal-800 border-teal-200',
            admin: 'bg-blue-100 text-blue-800 border-blue-200',
            manager: 'bg-indigo-100 text-indigo-800 border-indigo-200',
            member: 'bg-gray-100 text-gray-800 border-gray-200',
            viewer: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        };
        return colors[role] || colors.member;
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
                    <div className="flex items-center space-x-3">
                        <Button
                            onClick={() => setShowPasswordModal(true)}
                            variant="outline"
                        >
                            <Key className="w-4 h-4 mr-2" />
                            Change Password
                        </Button>
                        <Button
                            onClick={handleSendPasswordReset}
                            variant="outline"
                        >
                            <Mail className="w-4 h-4 mr-2" />
                            Send Reset Email
                        </Button>
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
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-green-800">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Password Change Modal */}
                {showPasswordModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <Card className="p-6 max-w-md w-full mx-4">
                            <h3 className="text-lg font-semibold mb-4">Change Password for {user.email}</h3>
                            <form onSubmit={handleChangePassword} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        New Password
                                    </label>
                                    <input
                                        type="password"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                        minLength={8}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Confirm Password
                                    </label>
                                    <input
                                        type="password"
                                        value={passwordConfirmation}
                                        onChange={(e) => setPasswordConfirmation(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                        minLength={8}
                                    />
                                </div>
                                <div className="flex items-center justify-end space-x-3 pt-4">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setShowPasswordModal(false);
                                            setPassword('');
                                            setPasswordConfirmation('');
                                        }}
                                        disabled={processing}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                    >
                                        {processing ? 'Changing...' : 'Change Password'}
                                    </Button>
                                </div>
                            </form>
                        </Card>
                    </div>
                )}

                {/* Roles & Organizations */}
                <Card className="p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <UserCog className="w-5 h-5" />
                            Roles & Organizations
                        </h3>
                    </div>
                    {user.organizations && user.organizations.length > 0 ? (
                        <div className="space-y-4">
                            {user.organizations.map((org) => {
                                const currentRole = org.pivot?.role || 'member';
                                return (
                                    <div key={org.id} className="border border-gray-200 rounded-lg p-4">
                                        <div className="flex items-center justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <Building2 className="w-5 h-5 text-blue-500" />
                                                    <h4 className="font-medium text-gray-900">{org.name}</h4>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <span className="text-sm text-gray-600">Current Role:</span>
                                                    <span className={`px-3 py-1 text-sm font-medium rounded-full border ${getRoleColor(currentRole)}`}>
                                                        {currentRole.charAt(0).toUpperCase() + currentRole.slice(1)}
                                                    </span>
                                                </div>
                                                {org.pivot?.joined_at && (
                                                    <p className="text-xs text-gray-500 mt-1">
                                                        Joined: {new Date(org.pivot.joined_at).toLocaleDateString()}
                                                    </p>
                                                )}
                                            </div>
                                            {roles && roles.length > 0 && (
                                                <div className="flex items-center gap-2">
                                                    <select
                                                        value={currentRole}
                                                        onChange={(e) => handleChangeRole(org.id, e.target.value)}
                                                        className="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                    >
                                                        {roles.map((role) => (
                                                            <option key={role.id} value={role.slug}>
                                                                {role.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500">User is not a member of any organizations</p>
                    )}
                </Card>

                {/* Stats */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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

