import { Head, useForm, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft, Save } from 'lucide-react';
import { useState } from 'react';

export default function EditEmployee({ employee, departments }) {
    const [activeTab, setActiveTab] = useState('personal');

    const { data, setData, put, processing, errors } = useForm({
        first_name: employee?.first_name || '',
        last_name: employee?.last_name || '',
        email: employee?.email || '',
        phone: employee?.phone || '',
        employee_number: employee?.employee_number || '',
        job_title: employee?.job_title || '',
        department_id: employee?.department_id || '',
        hire_date: employee?.hire_date || '',
        employment_type: employee?.employment_type || '',
        salary: employee?.salary || '',
        is_active: employee?.is_active ?? true,
        address: {
            street: employee?.address?.street || '',
            city: employee?.address?.city || '',
            state: employee?.address?.state || '',
            postal_code: employee?.address?.postal_code || '',
            country: employee?.address?.country || '',
        },
        emergency_contact: {
            name: employee?.emergency_contact?.name || '',
            relationship: employee?.emergency_contact?.relationship || '',
            phone: employee?.emergency_contact?.phone || '',
            email: employee?.emergency_contact?.email || '',
        },
        // Bank Details
        bank_name: employee?.bank_name || '',
        bank_account_name: employee?.bank_account_name || '',
        bank_account_number: employee?.bank_account_number || '',
        bank_branch_code: employee?.bank_branch_code || '',
        bank_sort_code: employee?.bank_sort_code || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('hr.employees.update', employee?.id), {
            preserveScroll: true,
        });
    };

    const updateAddress = (field, value) => {
        setData('address', {
            ...data.address,
            [field]: value,
        });
    };

    const updateEmergencyContact = (field, value) => {
        setData('emergency_contact', {
            ...data.emergency_contact,
            [field]: value,
        });
    };

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
            <Head title={`Edit ${employee.first_name} ${employee.last_name}`} />
            <div className="max-w-5xl mx-auto">
                <div className="mb-6">
                    <Link 
                        href={route('hr.employees.show', employee.id)} 
                        className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Employee Profile
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Edit Employee</h1>
                    <p className="text-gray-500 mt-1">Update employee information</p>
                </div>

                <Card className="p-6">
                    {/* Tabs */}
                    <div className="border-b border-gray-200 mb-6">
                        <nav className="flex space-x-8">
                            {['personal', 'employment', 'bank', 'address', 'emergency'].map((tab) => (
                                <button
                                    key={tab}
                                    onClick={() => setActiveTab(tab)}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === tab
                                            ? 'border-teal-500 text-teal-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    {tab.charAt(0).toUpperCase() + tab.slice(1).replace('_', ' ')}
                                </button>
                            ))}
                        </nav>
                    </div>

                    <form onSubmit={handleSubmit}>
                        {/* Personal Information Tab */}
                        {activeTab === 'personal' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        First Name *
                                    </label>
                                    <input
                                        type="text"
                                        value={data.first_name}
                                        onChange={(e) => setData('first_name', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        required
                                    />
                                    {errors.first_name && <p className="text-red-500 text-sm mt-1">{errors.first_name}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Last Name *
                                    </label>
                                    <input
                                        type="text"
                                        value={data.last_name}
                                        onChange={(e) => setData('last_name', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        required
                                    />
                                    {errors.last_name && <p className="text-red-500 text-sm mt-1">{errors.last_name}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Email
                                    </label>
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                    {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Phone
                                    </label>
                                    <input
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                    {errors.phone && <p className="text-red-500 text-sm mt-1">{errors.phone}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Employee Number
                                    </label>
                                    <input
                                        type="text"
                                        value={data.employee_number}
                                        onChange={(e) => setData('employee_number', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                    {errors.employee_number && <p className="text-red-500 text-sm mt-1">{errors.employee_number}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Status
                                    </label>
                                    <select
                                        value={data.is_active ? '1' : '0'}
                                        onChange={(e) => setData('is_active', e.target.value === '1')}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        )}

                        {/* Employment Information Tab */}
                        {activeTab === 'employment' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Job Title
                                    </label>
                                    <input
                                        type="text"
                                        value={data.job_title}
                                        onChange={(e) => setData('job_title', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                    {errors.job_title && <p className="text-red-500 text-sm mt-1">{errors.job_title}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Department
                                    </label>
                                    <select
                                        value={data.department_id}
                                        onChange={(e) => setData('department_id', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">Select Department</option>
                                        {departments?.map((dept) => (
                                            <option key={dept.id} value={dept.id}>
                                                {dept.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.department_id && <p className="text-red-500 text-sm mt-1">{errors.department_id}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Employment Type
                                    </label>
                                    <select
                                        value={data.employment_type}
                                        onChange={(e) => setData('employment_type', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">Select Type</option>
                                        <option value="full_time">Full Time</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="contract">Contract</option>
                                        <option value="freelance">Freelance</option>
                                    </select>
                                    {errors.employment_type && <p className="text-red-500 text-sm mt-1">{errors.employment_type}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Hire Date
                                    </label>
                                    <input
                                        type="date"
                                        value={data.hire_date}
                                        onChange={(e) => setData('hire_date', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                    {errors.hire_date && <p className="text-red-500 text-sm mt-1">{errors.hire_date}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Salary
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.salary}
                                        onChange={(e) => setData('salary', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="0.00"
                                    />
                                    {errors.salary && <p className="text-red-500 text-sm mt-1">{errors.salary}</p>}
                                </div>
                            </div>
                        )}

                        {/* Bank Details Tab */}
                        {activeTab === 'bank' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Bank Name
                                    </label>
                                    <input
                                        type="text"
                                        value={data.bank_name}
                                        onChange={(e) => setData('bank_name', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="e.g., First National Bank"
                                    />
                                    {errors.bank_name && <p className="text-red-500 text-sm mt-1">{errors.bank_name}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Account Name
                                    </label>
                                    <input
                                        type="text"
                                        value={data.bank_account_name}
                                        onChange={(e) => setData('bank_account_name', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="Account holder name"
                                    />
                                    {errors.bank_account_name && <p className="text-red-500 text-sm mt-1">{errors.bank_account_name}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Account Number
                                    </label>
                                    <input
                                        type="text"
                                        value={data.bank_account_number}
                                        onChange={(e) => setData('bank_account_number', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="Bank account number"
                                    />
                                    {errors.bank_account_number && <p className="text-red-500 text-sm mt-1">{errors.bank_account_number}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Branch Code
                                    </label>
                                    <input
                                        type="text"
                                        value={data.bank_branch_code}
                                        onChange={(e) => setData('bank_branch_code', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="Branch code"
                                    />
                                    {errors.bank_branch_code && <p className="text-red-500 text-sm mt-1">{errors.bank_branch_code}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Sort Code
                                    </label>
                                    <input
                                        type="text"
                                        value={data.bank_sort_code}
                                        onChange={(e) => setData('bank_sort_code', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="Sort code"
                                    />
                                    {errors.bank_sort_code && <p className="text-red-500 text-sm mt-1">{errors.bank_sort_code}</p>}
                                </div>
                            </div>
                        )}

                        {/* Address Tab */}
                        {activeTab === 'address' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Street Address
                                    </label>
                                    <input
                                        type="text"
                                        value={data.address.street}
                                        onChange={(e) => updateAddress('street', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        City
                                    </label>
                                    <input
                                        type="text"
                                        value={data.address.city}
                                        onChange={(e) => updateAddress('city', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        State/Province
                                    </label>
                                    <input
                                        type="text"
                                        value={data.address.state}
                                        onChange={(e) => updateAddress('state', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Postal Code
                                    </label>
                                    <input
                                        type="text"
                                        value={data.address.postal_code}
                                        onChange={(e) => updateAddress('postal_code', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Country
                                    </label>
                                    <input
                                        type="text"
                                        value={data.address.country}
                                        onChange={(e) => updateAddress('country', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>
                            </div>
                        )}

                        {/* Emergency Contact Tab */}
                        {activeTab === 'emergency' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Contact Name
                                    </label>
                                    <input
                                        type="text"
                                        value={data.emergency_contact.name}
                                        onChange={(e) => updateEmergencyContact('name', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Relationship
                                    </label>
                                    <input
                                        type="text"
                                        value={data.emergency_contact.relationship}
                                        onChange={(e) => updateEmergencyContact('relationship', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="e.g., Spouse, Parent, Sibling"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Phone
                                    </label>
                                    <input
                                        type="tel"
                                        value={data.emergency_contact.phone}
                                        onChange={(e) => updateEmergencyContact('phone', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Email
                                    </label>
                                    <input
                                        type="email"
                                        value={data.emergency_contact.email}
                                        onChange={(e) => updateEmergencyContact('email', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>
                            </div>
                        )}

                        <div className="mt-6 flex justify-end gap-4 pt-6 border-t">
                            <Link href={route('hr.employees.show', employee.id)}>
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </SectionLayout>
    );
}
