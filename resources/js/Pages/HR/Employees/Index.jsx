import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import DepartmentModal from '@/Components/Departments/DepartmentModal';
import { Plus, Users, Search, Filter, Edit, Eye, Trash2, X, ChevronLeft, ChevronRight, Upload, Download, FileSpreadsheet } from 'lucide-react';
import { useState, useRef } from 'react';

export default function EmployeesIndex({ employees, departments, filters }) {
    const { flash } = usePage().props;
    const [showFilters, setShowFilters] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [showDepartmentModal, setShowDepartmentModal] = useState(false);
    const [importing, setImporting] = useState(false);
    const fileInputRef = useRef(null);
    
    const { data, setData, get } = useForm({
        search: filters?.search || '',
        department_id: filters?.department_id || '',
        is_active: filters?.is_active || '',
        employment_type: filters?.employment_type || '',
        sort_by: filters?.sort_by || 'first_name',
        sort_dir: filters?.sort_dir || 'asc',
    });

    const handleFilter = (e) => {
        e.preventDefault();
        get(route('hr.employees.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setData({
            search: '',
            department_id: '',
            is_active: '',
            employment_type: '',
            sort_by: 'first_name',
            sort_dir: 'asc',
        });
        router.get(route('hr.employees.index'), {}, {
            preserveState: false,
        });
    };

    const handleSort = (field) => {
        const newSortDir = data.sort_by === field && data.sort_dir === 'asc' ? 'desc' : 'asc';
        setData({
            ...data,
            sort_by: field,
            sort_dir: newSortDir,
        });
        get(route('hr.employees.index'), {
            preserveState: true,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
            router.delete(route('hr.employees.destroy', id), {
                preserveScroll: true,
            });
        }
    };

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
                    <div className="flex items-center gap-3">
                        <a href={route('hr.employees.download-template')} className="inline-flex">
                            <Button variant="secondary">
                                <Download className="h-4 w-4 mr-2" />
                                Download Template
                            </Button>
                        </a>
                        <Button variant="secondary" onClick={() => setShowImportModal(true)}>
                            <Upload className="h-4 w-4 mr-2" />
                            Import CSV
                        </Button>
                        <Link href={route('hr.employees.create')}>
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                Add Employee
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Success/Error Messages */}
                {flash?.message && (
                    <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p className="text-green-800">{flash.message}</p>
                    </div>
                )}
                {flash?.import_errors && flash.import_errors.length > 0 && (
                    <div className="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p className="text-yellow-800 font-medium mb-2">Import Warnings:</p>
                        <ul className="list-disc list-inside text-sm text-yellow-700">
                            {flash.import_errors.map((error, index) => (
                                <li key={index}>{error}</li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Filters */}
                <Card className="p-4 mb-6">
                    <div className="flex items-center justify-between mb-4">
                        <button
                            onClick={() => setShowFilters(!showFilters)}
                            className="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                        >
                            <Filter className="h-4 w-4" />
                            Filters
                            {showFilters && <X className="h-4 w-4" />}
                        </button>
                        {(data.search || data.department_id || data.is_active || data.employment_type) && (
                            <button
                                onClick={clearFilters}
                                className="text-sm text-teal-600 hover:text-teal-700"
                            >
                                Clear Filters
                            </button>
                        )}
                    </div>

                    {showFilters && (
                        <form onSubmit={handleFilter} className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        type="text"
                                        value={data.search}
                                        onChange={(e) => setData('search', e.target.value)}
                                        placeholder="Name, email, employee #..."
                                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <div className="flex items-center justify-between mb-1">
                                    <label className="block text-sm font-medium text-gray-700">Department</label>
                                    <button
                                        type="button"
                                        onClick={() => setShowDepartmentModal(true)}
                                        className="text-xs text-teal-600 hover:text-teal-700 font-medium"
                                    >
                                        + Add New
                                    </button>
                                </div>
                                <select
                                    value={data.department_id}
                                    onChange={(e) => setData('department_id', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="">All Departments</option>
                                    {departments?.map((dept) => (
                                        <option key={dept.id} value={dept.id}>
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    value={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Employment Type</label>
                                <select
                                    value={data.employment_type}
                                    onChange={(e) => setData('employment_type', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="">All Types</option>
                                    <option value="full_time">Full Time</option>
                                    <option value="part_time">Part Time</option>
                                    <option value="contract">Contract</option>
                                    <option value="freelance">Freelance</option>
                                </select>
                            </div>

                            <div className="md:col-span-4 flex justify-end">
                                <Button type="submit">Apply Filters</Button>
                            </div>
                        </form>
                    )}
                </Card>

                <Card className="p-6">
                    {employees && employees.data && employees.data.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-left">
                                    <thead>
                                        <tr className="text-xs uppercase text-gray-500 border-b">
                                            <th 
                                                className="py-3 pr-4 cursor-pointer hover:text-gray-700"
                                                onClick={() => handleSort('first_name')}
                                            >
                                                <div className="flex items-center gap-2">
                                                    Name
                                                    {data.sort_by === 'first_name' && (
                                                        <span className="text-teal-600">
                                                            {data.sort_dir === 'asc' ? '↑' : '↓'}
                                                        </span>
                                                    )}
                                                </div>
                                            </th>
                                            <th className="py-3 pr-4">Employee #</th>
                                            <th className="py-3 pr-4">Job Title</th>
                                            <th className="py-3 pr-4">Department</th>
                                            <th className="py-3 pr-4">Email</th>
                                            <th className="py-3 pr-4">Phone</th>
                                            <th className="py-3 pr-4">Type</th>
                                            <th className="py-3 pr-4">Status</th>
                                            <th className="py-3 pr-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {employees.data.map((emp) => (
                                            <tr key={emp.id} className="border-b border-gray-100 hover:bg-gray-50">
                                                <td className="py-3 pr-4 font-medium text-gray-900">
                                                    {emp.full_name}
                                                </td>
                                                <td className="py-3 pr-4 text-gray-600">{emp.employee_number || '-'}</td>
                                                <td className="py-3 pr-4">{emp.job_title || '-'}</td>
                                                <td className="py-3 pr-4">
                                                    {emp.department?.name || '-'}
                                                </td>
                                                <td className="py-3 pr-4 text-gray-600">{emp.email || '-'}</td>
                                                <td className="py-3 pr-4 text-gray-600">{emp.phone || '-'}</td>
                                                <td className="py-3 pr-4">
                                                    <span className="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">
                                                        {emp.employment_type ? emp.employment_type.replace('_', ' ') : '-'}
                                                    </span>
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <span className={`text-xs px-2 py-1 rounded ${
                                                        emp.is_active 
                                                            ? 'bg-green-100 text-green-700' 
                                                            : 'bg-red-100 text-red-700'
                                                    }`}>
                                                        {emp.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Link href={route('hr.employees.show', emp.id)}>
                                                            <Button variant="ghost" size="sm">
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                        <Link href={route('hr.employees.edit', emp.id)}>
                                                            <Button variant="ghost" size="sm">
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDelete(emp.id)}
                                                            className="text-red-600 hover:text-red-700"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {employees.links && employees.links.length > 3 && (
                                <div className="mt-6 flex items-center justify-between">
                                    <div className="text-sm text-gray-700">
                                        Showing {employees.from} to {employees.to} of {employees.total} employees
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {employees.links.map((link, index) => (
                                            <button
                                                key={index}
                                                onClick={() => link.url && router.get(link.url)}
                                                disabled={!link.url}
                                                className={`px-3 py-2 text-sm rounded-lg ${
                                                    link.active
                                                        ? 'bg-teal-600 text-white'
                                                        : link.url
                                                        ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="text-center py-12">
                            <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Employees Found</h3>
                            <p className="text-gray-500 mb-6">
                                {data.search || data.department_id || data.is_active || data.employment_type
                                    ? 'Try adjusting your filters'
                                    : 'Get started by adding your first employee'}
                            </p>
                            <Link href={route('hr.employees.create')}>
                                <Button>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Add Employee
                                </Button>
                            </Link>
                        </div>
                    )}
                </Card>
            </div>

            {/* CSV Import Modal */}
            {showImportModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <Card className="max-w-lg w-full">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-6">
                                <div className="flex items-center gap-3">
                                    <FileSpreadsheet className="h-6 w-6 text-teal-600" />
                                    <h2 className="text-xl font-bold text-gray-900">Import Employees</h2>
                                </div>
                                <button
                                    onClick={() => setShowImportModal(false)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <X className="h-6 w-6" />
                                </button>
                            </div>

                            <div className="space-y-4">
                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p className="text-sm text-blue-800">
                                        <strong>CSV Format:</strong> Your CSV file should contain the following columns:
                                    </p>
                                    <p className="text-xs text-blue-700 mt-2">
                                        first_name, last_name, email, phone, employee_number, job_title, department, hire_date, employment_type, salary, bank_name, bank_account_name, bank_account_number, bank_branch_code, bank_sort_code
                                    </p>
                                    <a
                                        href={route('hr.employees.download-template')}
                                        className="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 mt-3"
                                    >
                                        <Download className="h-4 w-4" />
                                        Download template
                                    </a>
                                </div>

                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const formData = new FormData();
                                        const file = fileInputRef.current?.files[0];
                                        if (!file) {
                                            alert('Please select a CSV file');
                                            return;
                                        }
                                        formData.append('csv_file', file);
                                        setImporting(true);
                                        router.post(route('hr.employees.import-csv'), formData, {
                                            forceFormData: true,
                                            onSuccess: () => {
                                                setShowImportModal(false);
                                                setImporting(false);
                                                if (fileInputRef.current) {
                                                    fileInputRef.current.value = '';
                                                }
                                            },
                                            onError: () => {
                                                setImporting(false);
                                            },
                                        });
                                    }}
                                    className="space-y-4"
                                >
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Select CSV File
                                        </label>
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept=".csv,.txt"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                            required
                                        />
                                    </div>

                                    <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => setShowImportModal(false)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={importing}>
                                            {importing ? 'Importing...' : 'Import Employees'}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </Card>
                </div>
            )}

            {/* Department Modal */}
            <DepartmentModal
                isOpen={showDepartmentModal}
                onClose={() => setShowDepartmentModal(false)}
                onDepartmentCreated={() => {
                    router.reload({ only: ['departments'] });
                }}
            />
        </SectionLayout>
    );
}
