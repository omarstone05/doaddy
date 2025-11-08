import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Edit, Users } from 'lucide-react';

export default function DepartmentsShow({ department }) {
    return (
        <AuthenticatedLayout>
            <Head title={department.name} />
            <div className="max-w-4xl mx-auto ">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{department.name}</h1>
                            <p className="text-gray-500 mt-1">Department Details</p>
                        </div>
                        <Link href={`/departments/${department.id}/edit`}>
                            <Button>
                                <Edit className="h-4 w-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Status</h3>
                            <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                                department.is_active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-gray-100 text-gray-700'
                            }`}>
                                {department.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Manager</h3>
                            {department.manager ? (
                                <p className="text-gray-900">
                                    {department.manager.first_name} {department.manager.last_name}
                                    {department.manager.job_title && (
                                        <span className="text-gray-500 ml-2">({department.manager.job_title})</span>
                                    )}
                                </p>
                            ) : (
                                <p className="text-gray-400">No manager assigned</p>
                            )}
                        </div>
                    </div>

                    {department.description && (
                        <div className="mb-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Description</h3>
                            <p className="text-gray-900">{department.description}</p>
                        </div>
                    )}

                    {/* Team Members */}
                    {department.team_members && department.team_members.length > 0 && (
                        <div className="pt-4 border-t border-gray-200">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <Users className="h-4 w-4" />
                                    Team Members ({department.team_members.length})
                                </h3>
                            </div>
                            <div className="space-y-2">
                                {department.team_members.map((member) => (
                                    <Link
                                        key={member.id}
                                        href={`/team/${member.id}`}
                                        className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                    >
                                        <div>
                                            <div className="font-medium text-gray-900">
                                                {member.first_name} {member.last_name}
                                            </div>
                                            {member.job_title && (
                                                <div className="text-sm text-gray-500">{member.job_title}</div>
                                            )}
                                        </div>
                                        <span className={`px-2 py-1 rounded text-xs font-medium ${
                                            member.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-100 text-gray-700'
                                        }`}>
                                            {member.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

