import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Edit, Trash2, User } from 'lucide-react';

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
            <Head title={`${employee.first_name || ''} ${employee.last_name || ''}`} />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href={route('hr.employees.index')} className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Employees
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                {employee.first_name || ''} {employee.last_name || ''}
                            </h1>
                            <p className="text-gray-500 mt-1">Employee Details</p>
                        </div>
                        <div className="flex gap-2">
                            <Link href={route('hr.employees.edit', employee.id)}>
                                <Button variant="outline">
                                    <Edit className="h-4 w-4 mr-2" />
                                    Edit
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <Card className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Employee Number</h3>
                            <p className="text-gray-900">{employee.employee_number || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Email</h3>
                            <p className="text-gray-900">{employee.email || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Phone</h3>
                            <p className="text-gray-900">{employee.phone || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Position</h3>
                            <p className="text-gray-900">{employee.position || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Department</h3>
                            <p className="text-gray-900">{employee.department || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Hire Date</h3>
                            <p className="text-gray-900">
                                {employee.hire_date ? new Date(employee.hire_date).toLocaleDateString() : 'N/A'}
                            </p>
                        </div>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

