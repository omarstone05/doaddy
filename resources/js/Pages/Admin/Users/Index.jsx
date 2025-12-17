import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Link, router } from '@inertiajs/react';
import { Users, Search, Eye } from 'lucide-react';
import { useState } from 'react';
import axios from 'axios';

export default function Index({ users, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [activeFilter, setActiveFilter] = useState(filters.is_active ?? null);
    const [togglingUsers, setTogglingUsers] = useState(new Set());

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/users', { search, is_active: activeFilter }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFilterChange = (value) => {
        setActiveFilter(value);
        router.get('/admin/users', { search, is_active: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleToggleActive = async (userId, currentStatus) => {
        if (togglingUsers.has(userId)) return;
        
        setTogglingUsers(prev => new Set(prev).add(userId));
        
        try {
            const response = await axios.post(`/admin/users/${userId}/toggle-active`);
            
            // Reload the page to reflect changes
            router.reload({
                preserveState: true,
                preserveScroll: true,
            });
        } catch (error) {
            console.error('Error toggling user active status:', error);
            alert('Failed to update user status. Please try again.');
        } finally {
            setTogglingUsers(prev => {
                const next = new Set(prev);
                next.delete(userId);
                return next;
            });
        }
    };

    return (
        <AdminLayout title="Users">
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Users</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Manage all users on the platform
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
                                placeholder="Search users..."
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <select
                                value={activeFilter ?? ''}
                                onChange={(e) => handleFilterChange(e.target.value || null)}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="">All Users</option>
                                <option value="true">Active Only</option>
                                <option value="false">Inactive Only</option>
                            </select>
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
                                        User
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Organization
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
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
                                {users.data.map((user) => (
                                    <tr key={user.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center">
                                                <div className="w-10 h-10 bg-teal-500 rounded-full flex items-center justify-center text-white font-semibold">
                                                    {user.name.charAt(0).toUpperCase()}
                                                </div>
                                                <div className="ml-4">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {user.name}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {user.email}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {user.organizations && user.organizations.length > 0 ? (
                                                <div className="space-y-1">
                                                    {user.organizations.slice(0, 2).map((org, idx) => (
                                                        <div key={org.id} className="text-sm">
                                                            {org.name}
                                                        </div>
                                                    ))}
                                                    {user.organizations.length > 2 && (
                                                        <div className="text-xs text-gray-500">
                                                            +{user.organizations.length - 2} more
                                                        </div>
                                                    )}
                                                </div>
                                            ) : (
                                                'N/A'
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex flex-wrap gap-1">
                                                {user.is_super_admin && (
                                                    <span className="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                                        Super Admin
                                                    </span>
                                                )}
                                                {user.organizations && user.organizations.length > 0 ? (
                                                    user.organizations.slice(0, 3).map((org) => {
                                                        const role = org.pivot?.role || 'member';
                                                        const roleColors = {
                                                            owner: 'bg-teal-100 text-teal-800 border-teal-200',
                                                            admin: 'bg-blue-100 text-blue-800 border-blue-200',
                                                            manager: 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                                            member: 'bg-gray-100 text-gray-800 border-gray-200',
                                                            viewer: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                        };
                                                        return (
                                                            <div key={org.id} className="flex flex-col gap-0.5">
                                                                <span
                                                                    className={`px-2 py-1 text-xs font-medium rounded-full border ${roleColors[role] || roleColors.member}`}
                                                                    title={`${org.name}: ${role}`}
                                                                >
                                                                    {role.charAt(0).toUpperCase() + role.slice(1)}
                                                                </span>
                                                                <span className="text-xs text-gray-500 truncate max-w-[100px]" title={org.name}>
                                                                    {org.name}
                                                                </span>
                                                            </div>
                                                        );
                                                    })
                                                ) : (
                                                    !user.is_super_admin && (
                                                        <span className="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                                            No Role
                                                        </span>
                                                    )
                                                )}
                                                {user.organizations && user.organizations.length > 3 && (
                                                    <span className="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                                        +{user.organizations.length - 3} more
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center gap-3">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    user.is_active 
                                                        ? 'bg-green-100 text-green-700' 
                                                        : 'bg-gray-100 text-gray-700'
                                                }`}>
                                                    {user.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                                <label className="relative inline-flex items-center cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={user.is_active ?? true}
                                                        onChange={() => handleToggleActive(user.id, user.is_active)}
                                                        disabled={togglingUsers.has(user.id)}
                                                        className="sr-only peer"
                                                    />
                                                    <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
                                                </label>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {new Date(user.created_at).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link
                                                href={`/admin/users/${user.id}`}
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
                    {users.links && users.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-700">
                                    Showing {users.from} to {users.to} of {users.total} results
                                </div>
                                <div className="flex space-x-2">
                                    {users.links.map((link, index) => (
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

