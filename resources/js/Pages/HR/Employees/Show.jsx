import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Edit, Mail, Phone, MapPin, Calendar, DollarSign, Briefcase, Users, FileText, Clock, UserCheck, UserX, Building2, Landmark, CreditCard } from 'lucide-react';

export default function ShowEmployee({ employee }) {
    if (!employee) {
        return (
            <SectionLayout sectionName="HR">
                <Head title="Employee Not Found" />
                <div className="max-w-4xl mx-auto">
                    <Card className="p-6 text-center">
                        <p className="text-gray-500">Employee not found</p>
                        <Link href={route('hr.employees.index')} className="mt-4 inline-block">
                            <Button>Back to Employees</Button>
                        </Link>
                    </Card>
                </div>
            </SectionLayout>
        );
    }

    return (
        <SectionLayout sectionName="HR">
            <Head title={`${employee.full_name} - Employee Profile`} />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <Link 
                        href={route('hr.employees.index')} 
                        className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Employees
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <div className="flex items-center gap-3 mb-2">
                                <h1 className="text-3xl font-bold text-gray-900">{employee.full_name}</h1>
                                <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                                    employee.is_active 
                                        ? 'bg-green-100 text-green-700' 
                                        : 'bg-red-100 text-red-700'
                                }`}>
                                    {employee.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                            <p className="text-gray-500">{employee.job_title || 'Employee'}</p>
                        </div>
                        <Link href={route('hr.employees.edit', employee.id)}>
                            <Button>
                                <Edit className="h-4 w-4 mr-2" />
                                Edit Profile
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Personal Information */}
                        <Card className="p-6">
                            <h2 className="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <Users className="h-5 w-5 text-teal-600" />
                                Personal Information
                            </h2>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="text-sm font-medium text-gray-500">First Name</label>
                                    <p className="text-gray-900 mt-1">{employee.first_name}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Last Name</label>
                                    <p className="text-gray-900 mt-1">{employee.last_name}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                        <Mail className="h-4 w-4" />
                                        Email
                                    </label>
                                    <p className="text-gray-900 mt-1">{employee.email || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                        <Phone className="h-4 w-4" />
                                        Phone
                                    </label>
                                    <p className="text-gray-900 mt-1">{employee.phone || 'N/A'}</p>
                                </div>
                                {employee.address && (
                                    <div className="md:col-span-2">
                                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <MapPin className="h-4 w-4" />
                                            Address
                                        </label>
                                        <p className="text-gray-900 mt-1">
                                            {typeof employee.address === 'object' 
                                                ? Object.values(employee.address).filter(Boolean).join(', ') || 'N/A'
                                                : employee.address || 'N/A'}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </Card>

                        {/* Employment Information */}
                        <Card className="p-6">
                            <h2 className="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <Briefcase className="h-5 w-5 text-teal-600" />
                                Employment Information
                            </h2>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Employee Number</label>
                                    <p className="text-gray-900 mt-1">{employee.employee_number || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Job Title</label>
                                    <p className="text-gray-900 mt-1">{employee.job_title || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                        <Building2 className="h-4 w-4" />
                                        Department
                                    </label>
                                    <p className="text-gray-900 mt-1">{employee.department?.name || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Employment Type</label>
                                    <p className="text-gray-900 mt-1">
                                        {employee.employment_type 
                                            ? employee.employment_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
                                            : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                        <Calendar className="h-4 w-4" />
                                        Hire Date
                                    </label>
                                    <p className="text-gray-900 mt-1">
                                        {employee.hire_date 
                                            ? new Date(employee.hire_date).toLocaleDateString('en-US', { 
                                                year: 'numeric', 
                                                month: 'long', 
                                                day: 'numeric' 
                                            })
                                            : 'N/A'}
                                    </p>
                                </div>
                                {employee.tenure && (
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <Clock className="h-4 w-4" />
                                            Tenure
                                        </label>
                                        <p className="text-gray-900 mt-1">{employee.tenure.formatted}</p>
                                    </div>
                                )}
                                {employee.salary && (
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <DollarSign className="h-4 w-4" />
                                            Salary
                                        </label>
                                        <p className="text-gray-900 mt-1">
                                            {new Intl.NumberFormat('en-US', {
                                                style: 'currency',
                                                currency: 'ZMW',
                                            }).format(employee.salary)}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </Card>

                        {/* Bank Details */}
                        {(employee.bank_name || employee.bank_account_number) && (
                            <Card className="p-6">
                                <h2 className="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <Landmark className="h-5 w-5 text-teal-600" />
                                    Bank Details
                                </h2>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Bank Name</label>
                                        <p className="text-gray-900 mt-1">{employee.bank_name || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Account Name</label>
                                        <p className="text-gray-900 mt-1">{employee.bank_account_name || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CreditCard className="h-4 w-4" />
                                            Account Number
                                        </label>
                                        <p className="text-gray-900 mt-1 font-mono">{employee.bank_account_number || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Branch Code</label>
                                        <p className="text-gray-900 mt-1">{employee.bank_branch_code || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Sort Code</label>
                                        <p className="text-gray-900 mt-1">{employee.bank_sort_code || 'N/A'}</p>
                                    </div>
                                </div>
                            </Card>
                        )}

                        {/* Emergency Contact */}
                        {employee.emergency_contact && Object.keys(employee.emergency_contact).length > 0 && (
                            <Card className="p-6">
                                <h2 className="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <Phone className="h-5 w-5 text-teal-600" />
                                    Emergency Contact
                                </h2>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {Object.entries(employee.emergency_contact).map(([key, value]) => (
                                        <div key={key}>
                                            <label className="text-sm font-medium text-gray-500">
                                                {key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                            </label>
                                            <p className="text-gray-900 mt-1">{value || 'N/A'}</p>
                                        </div>
                                    ))}
                                </div>
                            </Card>
                        )}

                        {/* Recent Activity */}
                        {(employee.recent_leave_requests?.length > 0 || employee.recent_sales?.length > 0) && (
                            <Card className="p-6">
                                <h2 className="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <Clock className="h-5 w-5 text-teal-600" />
                                    Recent Activity
                                </h2>
                                <div className="space-y-4">
                                    {employee.recent_leave_requests?.length > 0 && (
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-700 mb-2">Recent Leave Requests</h3>
                                            <div className="space-y-2">
                                                {employee.recent_leave_requests.map((request) => (
                                                    <div key={request.id} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-900">{request.leave_type}</p>
                                                            <p className="text-xs text-gray-500">
                                                                {new Date(request.start_date).toLocaleDateString()} - {new Date(request.end_date).toLocaleDateString()}
                                                            </p>
                                                        </div>
                                                        <span className={`text-xs px-2 py-1 rounded ${
                                                            request.status === 'approved' ? 'bg-green-100 text-green-700' :
                                                            request.status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                                            'bg-red-100 text-red-700'
                                                        }`}>
                                                            {request.status}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    {employee.recent_sales?.length > 0 && (
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-700 mb-2">Recent Sales</h3>
                                            <div className="space-y-2">
                                                {employee.recent_sales.map((sale) => (
                                                    <div key={sale.id} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-900">{sale.sale_number}</p>
                                                            <p className="text-xs text-gray-500">
                                                                {new Date(sale.created_at).toLocaleDateString()}
                                                            </p>
                                                        </div>
                                                        <p className="text-sm font-medium text-gray-900">
                                                            {new Intl.NumberFormat('en-US', {
                                                                style: 'currency',
                                                                currency: 'ZMW',
                                                            }).format(sale.total_amount)}
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Account Status */}
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Account Status</h3>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-gray-600">User Account</span>
                                    {employee.has_user_account ? (
                                        <span className="flex items-center gap-1 text-green-600">
                                            <UserCheck className="h-4 w-4" />
                                            Linked
                                        </span>
                                    ) : (
                                        <span className="flex items-center gap-1 text-gray-400">
                                            <UserX className="h-4 w-4" />
                                            Not Linked
                                        </span>
                                    )}
                                </div>
                                {employee.user && (
                                    <div className="pt-3 border-t">
                                        <p className="text-sm text-gray-600 mb-1">Linked User</p>
                                        <p className="text-sm font-medium text-gray-900">{employee.user.name}</p>
                                        <p className="text-xs text-gray-500">{employee.user.email}</p>
                                    </div>
                                )}
                            </div>
                        </Card>

                        {/* Documents & Attachments */}
                        {(employee.attachments?.length > 0 || employee.documents?.length > 0) && (
                            <Card className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <FileText className="h-5 w-5 text-teal-600" />
                                    Documents
                                </h3>
                                <div className="space-y-3">
                                    {employee.attachments?.length > 0 && (
                                        <div>
                                            <p className="text-sm font-medium text-gray-700 mb-2">Attachments ({employee.attachments.length})</p>
                                            <div className="space-y-1">
                                                {employee.attachments.map((attachment) => (
                                                    <a
                                                        key={attachment.id}
                                                        href={`/storage/${attachment.file_path}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="block text-sm text-teal-600 hover:text-teal-700 truncate"
                                                    >
                                                        {attachment.name}
                                                    </a>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    {employee.documents?.length > 0 && (
                                        <div>
                                            <p className="text-sm font-medium text-gray-700 mb-2">Documents ({employee.documents.length})</p>
                                            <div className="space-y-1">
                                                {employee.documents.map((doc) => (
                                                    <p key={doc.id} className="text-sm text-gray-600">
                                                        {doc.name}
                                                    </p>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}
