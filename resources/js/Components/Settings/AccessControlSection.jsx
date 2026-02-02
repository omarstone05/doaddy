import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/Button';
import {
    Users,
    Plus,
    Search,
    Shield,
    Mail,
    MoreVertical,
    UserPlus,
    UserMinus,
    RefreshCw,
    ChevronDown,
} from 'lucide-react';

// Role badge colors
const roleBadgeColors = {
    owner: 'bg-purple-100 text-purple-700 border-purple-200',
    admin: 'bg-blue-100 text-blue-700 border-blue-200',
    manager: 'bg-teal-100 text-teal-700 border-teal-200',
    accountant: 'bg-amber-100 text-amber-700 border-amber-200',
    inventory_manager: 'bg-orange-100 text-orange-700 border-orange-200',
    sales_rep: 'bg-green-100 text-green-700 border-green-200',
    member: 'bg-gray-100 text-gray-700 border-gray-200',
    viewer: 'bg-slate-100 text-slate-600 border-slate-200',
};

export default function AccessControlSection({
    organizationUsers,
    organizationRoles,
    currentUserRole,
    filters,
}) {
    const [showInviteModal, setShowInviteModal] = useState(false);
    const [showRoleModal, setShowRoleModal] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);
    const [openDropdown, setOpenDropdown] = useState(null);

    const inviteForm = useForm({
        email: '',
        name: '',
        role_id: '',
    });

    const roleForm = useForm({
        role_id: '',
    });

    const canManageUsers = currentUserRole?.permissions?.includes('users.manage') ||
                           currentUserRole?.permissions?.includes('users.invite') ||
                           currentUserRole?.slug === 'owner' ||
                           currentUserRole?.slug === 'admin';

    const canChangeRoles = currentUserRole?.permissions?.includes('users.change_role') ||
                           currentUserRole?.slug === 'owner' ||
                           currentUserRole?.slug === 'admin';

    const handleInvite = (e) => {
        e.preventDefault();
        inviteForm.post('/settings/team/invite', {
            preserveScroll: true,
            onSuccess: () => {
                setShowInviteModal(false);
                inviteForm.reset();
            },
        });
    };

    const handleChangeRole = (e) => {
        e.preventDefault();
        if (!selectedUser) return;
        
        roleForm.put(`/settings/team/${selectedUser.id}/role`, {
            preserveScroll: true,
            onSuccess: () => {
                setShowRoleModal(false);
                setSelectedUser(null);
                roleForm.reset();
            },
        });
    };

    const handleRemoveUser = (userId, userName) => {
        if (confirm(`Are you sure you want to remove ${userName} from the organization? They will lose access to all organization data.`)) {
            router.delete(`/settings/team/${userId}`, {
                preserveScroll: true,
            });
        }
    };

    const handleToggleStatus = (userId) => {
        router.post(`/settings/team/${userId}/toggle-status`, {}, {
            preserveScroll: true,
        });
    };

    const openChangeRoleModal = (user) => {
        setSelectedUser(user);
        roleForm.setData('role_id', user.role?.id || '');
        setShowRoleModal(true);
        setOpenDropdown(null);
    };

    // Filter roles that current user can assign (only lower level roles)
    const assignableRoles = organizationRoles?.filter(role => {
        if (currentUserRole?.slug === 'owner') return true;
        return role.level < (currentUserRole?.level || 0);
    }) || [];

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-bold text-gray-900">Access Control</h2>
                    <p className="text-sm text-gray-500 mt-1">
                        Manage who has access to your organization and what they can do
                    </p>
                </div>
                {canManageUsers && (
                    <Button onClick={() => setShowInviteModal(true)}>
                        <UserPlus className="h-4 w-4 mr-2" />
                        Invite User
                    </Button>
                )}
            </div>

            {/* Filters */}
            <div className="bg-white rounded-2xl border border-gray-100 p-5">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input
                            type="text"
                            defaultValue={filters?.search || ''}
                            onChange={(e) => router.visit(`/settings/team?search=${e.target.value}`, { preserveState: true })}
                            placeholder="Search users..."
                            className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500"
                        />
                    </div>
                    <select
                        defaultValue={filters?.role || ''}
                        onChange={(e) => router.visit(`/settings/team?role=${e.target.value}`, { preserveState: true })}
                        className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm"
                    >
                        <option value="">All Roles</option>
                        {organizationRoles?.map((role) => (
                            <option key={role.id} value={role.slug}>{role.name}</option>
                        ))}
                    </select>
                    <select
                        defaultValue={filters?.status || ''}
                        onChange={(e) => router.visit(`/settings/team?status=${e.target.value}`, { preserveState: true })}
                        className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm"
                    >
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            {/* Users List */}
            {!organizationUsers || organizationUsers?.data?.length === 0 ? (
                <div className="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                    <Users className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">No users found</h3>
                    <p className="text-gray-500 mb-6">
                        {filters?.search || filters?.role || filters?.status
                            ? 'Try adjusting your filters'
                            : 'Invite users to give them access to this organization'}
                    </p>
                    {canManageUsers && !filters?.search && (
                        <Button onClick={() => setShowInviteModal(true)}>
                            <UserPlus className="h-4 w-4 mr-2" />
                            Invite User
                        </Button>
                    )}
                </div>
            ) : (
                <div className="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Last Active</th>
                                {canManageUsers && (
                                    <th className="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-50">
                            {organizationUsers?.data?.map((orgUser) => (
                                <tr key={orgUser.id} className="hover:bg-gray-50/50 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white text-sm font-bold">
                                                {orgUser.name?.charAt(0)?.toUpperCase() || '?'}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-gray-900">{orgUser.name}</p>
                                                <p className="text-xs text-gray-500">{orgUser.email}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        {orgUser.role ? (
                                            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ${roleBadgeColors[orgUser.role.slug] || roleBadgeColors.member}`}>
                                                <Shield className="h-3 w-3 mr-1" />
                                                {orgUser.role.name}
                                            </span>
                                        ) : (
                                            <span className="text-sm text-gray-400">No role</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                                            orgUser.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600'
                                        }`}>
                                            {orgUser.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4">
                                        <p className="text-sm text-gray-500">
                                            {orgUser.last_active_at
                                                ? new Date(orgUser.last_active_at).toLocaleDateString()
                                                : 'Never'}
                                        </p>
                                    </td>
                                    {canManageUsers && (
                                        <td className="px-6 py-4">
                                            <div className="flex items-center justify-center relative">
                                                <button
                                                    onClick={() => setOpenDropdown(openDropdown === orgUser.id ? null : orgUser.id)}
                                                    className="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                                                >
                                                    <MoreVertical className="h-4 w-4" />
                                                </button>
                                                
                                                {openDropdown === orgUser.id && (
                                                    <div className="absolute right-0 top-full mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-10">
                                                        {canChangeRoles && orgUser.role?.slug !== 'owner' && (
                                                            <button
                                                                onClick={() => openChangeRoleModal(orgUser)}
                                                                className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                                                            >
                                                                <Shield className="h-4 w-4" />
                                                                Change Role
                                                            </button>
                                                        )}
                                                        {orgUser.role?.slug !== 'owner' && (
                                                            <button
                                                                onClick={() => handleToggleStatus(orgUser.id)}
                                                                className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                                                            >
                                                                <RefreshCw className="h-4 w-4" />
                                                                {orgUser.is_active ? 'Deactivate' : 'Activate'}
                                                            </button>
                                                        )}
                                                        {orgUser.role?.slug !== 'owner' && (
                                                            <button
                                                                onClick={() => handleRemoveUser(orgUser.id, orgUser.name)}
                                                                className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                                                            >
                                                                <UserMinus className="h-4 w-4" />
                                                                Remove Access
                                                            </button>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    
                    {/* Pagination */}
                    {organizationUsers?.links?.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                            <p className="text-sm text-gray-600">
                                Showing <span className="font-semibold">{organizationUsers.from}</span> to <span className="font-semibold">{organizationUsers.to}</span> of <span className="font-semibold">{organizationUsers.total}</span>
                            </p>
                            <div className="flex gap-1">
                                {organizationUsers.links.map((link, index) => (
                                    <button
                                        key={index}
                                        onClick={() => link.url && router.visit(link.url)}
                                        disabled={!link.url}
                                        className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                                            link.active
                                                ? 'bg-teal-500 text-white'
                                                : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'
                                        } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Role Legend */}
            <div className="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 className="font-semibold text-gray-900 mb-4">Role Permissions</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {organizationRoles?.slice(0, 8).map((role) => (
                        <div key={role.id} className="p-3 rounded-xl border border-gray-100 hover:border-gray-200 transition-colors">
                            <div className="flex items-center gap-2 mb-2">
                                <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ${roleBadgeColors[role.slug] || roleBadgeColors.member}`}>
                                    {role.name}
                                </span>
                            </div>
                            <p className="text-xs text-gray-500">{role.description || `Level ${role.level} access`}</p>
                        </div>
                    ))}
                </div>
            </div>

            {/* Invite Modal */}
            {showInviteModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Invite User</h3>
                        <form onSubmit={handleInvite}>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Email Address *
                                    </label>
                                    <input
                                        type="email"
                                        value={inviteForm.data.email}
                                        onChange={(e) => inviteForm.setData('email', e.target.value)}
                                        className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500"
                                        placeholder="user@example.com"
                                        required
                                    />
                                    {inviteForm.errors.email && (
                                        <p className="mt-1 text-sm text-red-600">{inviteForm.errors.email}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Name (optional)
                                    </label>
                                    <input
                                        type="text"
                                        value={inviteForm.data.name}
                                        onChange={(e) => inviteForm.setData('name', e.target.value)}
                                        className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500"
                                        placeholder="John Doe"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Role *
                                    </label>
                                    <select
                                        value={inviteForm.data.role_id}
                                        onChange={(e) => inviteForm.setData('role_id', e.target.value)}
                                        className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500"
                                        required
                                    >
                                        <option value="">Select a role...</option>
                                        {assignableRoles.map((role) => (
                                            <option key={role.id} value={role.id}>
                                                {role.name}
                                            </option>
                                        ))}
                                    </select>
                                    {inviteForm.errors.role_id && (
                                        <p className="mt-1 text-sm text-red-600">{inviteForm.errors.role_id}</p>
                                    )}
                                </div>
                            </div>
                            <div className="flex gap-3 mt-6">
                                <Button type="submit" disabled={inviteForm.processing} className="flex-1">
                                    {inviteForm.processing ? 'Sending...' : 'Send Invitation'}
                                </Button>
                                <Button type="button" variant="secondary" onClick={() => setShowInviteModal(false)}>
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Change Role Modal */}
            {showRoleModal && selectedUser && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Change Role</h3>
                        <p className="text-sm text-gray-600 mb-4">
                            Change the role for <span className="font-semibold">{selectedUser.name}</span>
                        </p>
                        <form onSubmit={handleChangeRole}>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    New Role
                                </label>
                                <select
                                    value={roleForm.data.role_id}
                                    onChange={(e) => roleForm.setData('role_id', e.target.value)}
                                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500"
                                    required
                                >
                                    <option value="">Select a role...</option>
                                    {assignableRoles.map((role) => (
                                        <option key={role.id} value={role.id}>
                                            {role.name}
                                        </option>
                                    ))}
                                </select>
                                {roleForm.errors.role_id && (
                                    <p className="mt-1 text-sm text-red-600">{roleForm.errors.role_id}</p>
                                )}
                            </div>
                            <div className="flex gap-3 mt-6">
                                <Button type="submit" disabled={roleForm.processing} className="flex-1">
                                    {roleForm.processing ? 'Updating...' : 'Update Role'}
                                </Button>
                                <Button type="button" variant="secondary" onClick={() => {
                                    setShowRoleModal(false);
                                    setSelectedUser(null);
                                }}>
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Click outside to close dropdown */}
            {openDropdown && (
                <div 
                    className="fixed inset-0 z-0" 
                    onClick={() => setOpenDropdown(null)}
                />
            )}
        </div>
    );
}
