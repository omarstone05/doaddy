import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import DepartmentModal from '@/Components/Departments/DepartmentModal';
import { Plus, Eye, Edit, Trash2, Users, Building2, Search, Filter } from 'lucide-react';
import { useState } from 'react';

export default function TeamIndex({ teamMembers, departments, filters }) {
    const [showDepartmentModal, setShowDepartmentModal] = useState(false);

    const handleDelete = (memberId) => {
        if (confirm('Are you sure you want to delete this team member?')) {
            router.delete(`/settings/team/${memberId}`);
        }
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Team Members" />
            <div>
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Team Members</h1>
                        <p className="text-gray-500 mt-1">Manage your team</p>
                    </div>
                    <div className="flex gap-3">
                        <Button 
                            variant="secondary"
                            onClick={() => setShowDepartmentModal(true)}
                            className="gap-2"
                        >
                            <Building2 className="h-4 w-4" />
                            Add Department
                        </Button>
                        <Button onClick={() => router.visit('/settings/team/create')} className="gap-2">
                            <Plus className="h-4 w-4" />
                            New Team Member
                        </Button>
                    </div>
                </div>

                {/* Filters Card */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 mb-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                            <Filter className="w-5 h-5 text-teal-600" />
                        </div>
                        <h3 className="text-sm font-bold text-gray-900">Filters</h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Search</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={filters?.search || ''}
                                    onChange={(e) => router.visit(`/settings/team?search=${e.target.value}`)}
                                    placeholder="Name, email, or employee number..."
                                    className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Department</label>
                            <select
                                value={filters?.department_id || ''}
                                onChange={(e) => router.visit(`/settings/team?department_id=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            >
                                <option value="">All Departments</option>
                                {departments.map((dept) => (
                                    <option key={dept.id} value={dept.id}>{dept.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Status</label>
                            <select
                                value={filters?.is_active || ''}
                                onChange={(e) => router.visit(`/settings/team?is_active=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            >
                                <option value="">All Status</option>
                                <option value="true">Active</option>
                                <option value="false">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                {teamMembers.data.length === 0 ? (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-teal-100 flex items-center justify-center mx-auto mb-4">
                            <Users className="h-8 w-8 text-teal-500" />
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 mb-2">No team members yet</h3>
                        <p className="text-gray-500 mb-6">Add your first team member to get started</p>
                        <Button onClick={() => router.visit('/settings/team/create')} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Add Team Member
                        </Button>
                    </div>
                ) : (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50/80 border-b border-gray-200/50">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Department
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Job Title
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {teamMembers.data.map((member) => (
                                    <tr key={member.id} className="hover:bg-teal-50/30 transition-colors">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white font-bold">
                                                    {member.first_name?.charAt(0)}{member.last_name?.charAt(0)}
                                                </div>
                                                <div>
                                                    <p className="font-bold text-gray-900">
                                                        {member.first_name} {member.last_name}
                                                    </p>
                                                    {member.employee_number && (
                                                        <p className="text-xs text-gray-500">#{member.employee_number}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            {member.email && (
                                                <p className="text-sm font-medium text-gray-900">{member.email}</p>
                                            )}
                                            {member.phone && (
                                                <p className="text-xs text-gray-500">{member.phone}</p>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            {member.department ? (
                                                <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-700">
                                                    {member.department.name}
                                                </span>
                                            ) : (
                                                <span className="text-sm text-gray-400">No department</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {member.job_title || '-'}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                                                member.is_active
                                                    ? 'bg-teal-100 text-teal-700'
                                                    : 'bg-gray-100 text-gray-600'
                                            }`}>
                                                {member.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link
                                                    href={`/settings/team/${member.id}`}
                                                    className="p-2 rounded-lg text-teal-600 hover:bg-teal-100 transition-colors"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={`/settings/team/${member.id}/edit`}
                                                    className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(member.id)}
                                                    className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
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
                            <div className="px-6 py-4 border-t border-gray-200/50 flex items-center justify-between bg-gray-50/50">
                                <p className="text-sm text-gray-600">
                                    Showing <span className="font-semibold">{teamMembers.from}</span> to <span className="font-semibold">{teamMembers.to}</span> of <span className="font-semibold">{teamMembers.total}</span>
                                </p>
                                <div className="flex gap-1">
                                    {teamMembers.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                                                link.active
                                                    ? 'bg-teal-500 text-white shadow-sm'
                                                    : 'bg-white border border-gray-200 text-gray-700 hover:bg-teal-50 hover:border-teal-200'
                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Department Modal */}
                <DepartmentModal
                    isOpen={showDepartmentModal}
                    onClose={() => setShowDepartmentModal(false)}
                    onDepartmentCreated={() => {
                        router.reload();
                    }}
                />
            </div>
        </SectionLayout>
    );
}
