import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Edit, User, Building2, DollarSign } from 'lucide-react';

export default function TeamShow({ teamMember }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    return (
        <SectionLayout sectionName="People">
            <Head title={`${teamMember.first_name} ${teamMember.last_name}`} />
            <div className="max-w-4xl mx-auto">
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
                            <h1 className="text-3xl font-bold text-gray-900">
                                {teamMember.first_name} {teamMember.last_name}
                            </h1>
                            <p className="text-gray-500 mt-1">{teamMember.job_title || 'Team Member'}</p>
                        </div>
                        <Link href={`/team/${teamMember.id}/edit`}>
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
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Contact Information</h3>
                            <div className="space-y-2">
                                {teamMember.email && (
                                    <p className="text-gray-900">
                                        <span className="font-medium">Email:</span> {teamMember.email}
                                    </p>
                                )}
                                {teamMember.phone && (
                                    <p className="text-gray-900">
                                        <span className="font-medium">Phone:</span> {teamMember.phone}
                                    </p>
                                )}
                                {teamMember.user && (
                                    <p className="text-sm text-teal-600">
                                        Linked to user account
                                    </p>
                                )}
                            </div>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Department</h3>
                            {teamMember.department ? (
                                <Link
                                    href={`/departments/${teamMember.department.id}`}
                                    className="text-teal-600 hover:text-teal-700 font-medium"
                                >
                                    {teamMember.department.name}
                                </Link>
                            ) : (
                                <p className="text-gray-400">No department assigned</p>
                            )}
                        </div>
                    </div>

                    {/* Employment Details */}
                    <div className="pt-4 border-t border-gray-200">
                        <h3 className="text-sm font-medium text-gray-500 mb-4">Employment Details</h3>
                        <div className="grid grid-cols-2 gap-4">
                            {teamMember.employee_number && (
                                <div>
                                    <span className="text-sm text-gray-500">Employee Number:</span>
                                    <p className="text-gray-900 font-medium">{teamMember.employee_number}</p>
                                </div>
                            )}
                            {teamMember.hire_date && (
                                <div>
                                    <span className="text-sm text-gray-500">Hire Date:</span>
                                    <p className="text-gray-900 font-medium">
                                        {new Date(teamMember.hire_date).toLocaleDateString()}
                                    </p>
                                </div>
                            )}
                            {teamMember.job_title && (
                                <div>
                                    <span className="text-sm text-gray-500">Job Title:</span>
                                    <p className="text-gray-900 font-medium">{teamMember.job_title}</p>
                                </div>
                            )}
                            {teamMember.employment_type && (
                                <div>
                                    <span className="text-sm text-gray-500">Employment Type:</span>
                                    <p className="text-gray-900 font-medium capitalize">
                                        {teamMember.employment_type.replace('_', ' ')}
                                    </p>
                                </div>
                            )}
                            {teamMember.salary && (
                                <div>
                                    <span className="text-sm text-gray-500">Salary:</span>
                                    <p className="text-gray-900 font-medium">{formatCurrency(teamMember.salary)}</p>
                                </div>
                            )}
                            <div>
                                <span className="text-sm text-gray-500">Status:</span>
                                <span className={`ml-2 px-2 py-1 rounded-full text-xs font-medium ${
                                    teamMember.is_active
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-gray-100 text-gray-700'
                                }`}>
                                    {teamMember.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Sales Statistics */}
                {teamMember.sales && teamMember.sales.length > 0 && (
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <h2 className="text-xl font-semibold text-gray-900 mb-4">Sales Performance</h2>
                        <div className="grid grid-cols-3 gap-4">
                            <div className="p-4 bg-gray-50 rounded-lg">
                                <div className="text-sm text-gray-500 mb-1">Total Sales</div>
                                <div className="text-2xl font-bold text-gray-900">{teamMember.sales.length}</div>
                            </div>
                            <div className="p-4 bg-gray-50 rounded-lg">
                                <div className="text-sm text-gray-500 mb-1">Total Revenue</div>
                                <div className="text-2xl font-bold text-gray-900">
                                    {formatCurrency(teamMember.sales.reduce((sum, sale) => sum + (sale.total_amount || 0), 0))}
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

