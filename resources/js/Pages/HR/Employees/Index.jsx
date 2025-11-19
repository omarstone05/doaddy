import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Plus, Users } from 'lucide-react';

export default function EmployeesIndex({ employees }) {
    return (
        <SectionLayout sectionName="HR">
            <Head title="Employees" />
            <div className="max-w-7xl mx-auto">
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <div className="flex items-center gap-3 mb-2">
                            <Users className="h-6 w-6 text-teal-600" />
                            <h1 className="text-3xl font-bold text-gray-900">Employees</h1>
                        </div>
                        <p className="text-gray-500 mt-1">Manage your employee records</p>
                    </div>
                    <Link href="/hr/employees/create">
                        <Button>
                            <Plus className="h-4 w-4 mr-2" />
                            Add Employee
                        </Button>
                    </Link>
                </div>

                <Card className="p-6">
                    {employees && employees.length > 0 ? (
                        <div className="space-y-4">
                            {/* Employee list will go here */}
                            <p className="text-gray-500">Employee list coming soon...</p>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Employees Yet</h3>
                            <p className="text-gray-500 mb-6">Get started by adding your first employee</p>
                            <Link href="/hr/employees/create">
                                <Button>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Add Employee
                                </Button>
                            </Link>
                        </div>
                    )}
                </Card>
            </div>
        </SectionLayout>
    );
}

