import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, Edit, Trash2, Users } from 'lucide-react';

export default function TeamIndex({ teamMembers, departments, filters }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const handleDelete = (memberId) => {
        if (confirm('Are you sure you want to delete this team member?')) {
            router.delete(`/team/${memberId}`);
        }
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Team Members" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Team Members</h1>
                        <p className="text-gray-500 mt-1">Manage your team</p>
                    </div>
                    <Button onClick={() => router.visit('/team/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Team Member
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={filters?.search || ''}
                                onChange={(e) => router.visit(`/team?search=${e.target.value}`)}
                                placeholder="Name, email, or employee number..."
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select
                                value={filters?.department_id || ''}
                                onChange={(e) => router.visit(`/team?department_id=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Departments</option>
                                {departments.map((dept) => (
                                    <option key={dept.id} value={dept.id}>{dept.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.is_active || ''}
                                onChange={(e) => router.visit(`/team?is_active=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Status</option>
                                <option value="true">Active</option>
                                <option value="false">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                {teamMembers.data.length === 0 ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                        <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No team members yet</h3>
                        <p className="text-gray-500 mb-4">Add your first team member to get started</p>
                        <Button onClick={() => router.visit('/team/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            Add Team Member
                        </Button>
                    </div>
                ) : (
                    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Department
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Job Title
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Employee Number
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {teamMembers.data.map((member) => (
                                    <tr key={member.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">
                                                {member.first_name} {member.last_name}
                                            </div>
                                            {member.user && (
                                                <div className="text-xs text-gray-500">Linked to user account</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            {member.email && (
                                                <div className="text-sm text-gray-900">{member.email}</div>
                                            )}
                                            {member.phone && (
                                                <div className="text-sm text-gray-500">{member.phone}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            {member.department ? (
                                                <Link
                                                    href={`/departments/${member.department.id}`}
                                                    className="text-teal-600 hover:text-teal-700 text-sm"
                                                >
                                                    {member.department.name}
                                                </Link>
                                            ) : (
                                                <span className="text-sm text-gray-400">No department</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {member.job_title || '-'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {member.employee_number || '-'}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                                member.is_active
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-gray-100 text-gray-700'
                                            }`}>
                                                {member.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <Link
                                                    href={`/team/${member.id}`}
                                                    className="text-teal-500 hover:text-teal-600"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={`/team/${member.id}/edit`}
                                                    className="text-blue-500 hover:text-blue-600"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(member.id)}
                                                    className="text-red-500 hover:text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        
                        {/* Pagination */}
                        {teamMembers.links && teamMembers.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                <div className="text-sm text-gray-500">
                                    Showing {teamMembers.from} to {teamMembers.to} of {teamMembers.total} results
                                </div>
                                <div className="flex gap-2">
                                    {teamMembers.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-1 rounded-lg text-sm ${
                                                link.active
                                                    ? 'bg-teal-500 text-white'
                                                    : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

