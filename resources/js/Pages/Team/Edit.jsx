import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function TeamEdit({ teamMember, departments, users }) {
    const { data, setData, put, processing, errors } = useForm({
        first_name: teamMember.first_name,
        last_name: teamMember.last_name,
        email: teamMember.email || '',
        phone: teamMember.phone || '',
        employee_number: teamMember.employee_number || '',
        hire_date: teamMember.hire_date || '',
        job_title: teamMember.job_title || '',
        salary: teamMember.salary || '',
        employment_type: teamMember.employment_type || '',
        department_id: teamMember.department_id || '',
        user_id: teamMember.user_id || '',
        is_active: teamMember.is_active,
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/team/${teamMember.id}`);
    };

    return (
        <SectionLayout sectionName="People">
            <Head title={`Edit ${teamMember.first_name} ${teamMember.last_name}`} />
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
                    <h1 className="text-3xl font-bold text-gray-900">Edit Team Member</h1>
                    <p className="text-gray-500 mt-1">{teamMember.first_name} {teamMember.last_name}</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="first_name" className="block text-sm font-medium text-gray-700 mb-2">
                                    First Name *
                                </label>
                                <input
                                    id="first_name"
                                    type="text"
                                    value={data.first_name}
                                    onChange={(e) => setData('first_name', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.first_name && <p className="mt-1 text-sm text-red-600">{errors.first_name}</p>}
                            </div>

                            <div>
                                <label htmlFor="last_name" className="block text-sm font-medium text-gray-700 mb-2">
                                    Last Name *
                                </label>
                                <input
                                    id="last_name"
                                    type="text"
                                    value={data.last_name}
                                    onChange={(e) => setData('last_name', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.last_name && <p className="mt-1 text-sm text-red-600">{errors.last_name}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>

                            <div>
                                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-2">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    type="text"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="department_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Department
                                </label>
                                <select
                                    id="department_id"
                                    value={data.department_id}
                                    onChange={(e) => setData('department_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="">Select department</option>
                                    {departments.map((dept) => (
                                        <option key={dept.id} value={dept.id}>{dept.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label htmlFor="user_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Link to User Account
                                </label>
                                <select
                                    id="user_id"
                                    value={data.user_id}
                                    onChange={(e) => setData('user_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="">No user account</option>
                                    {users.map((user) => (
                                        <option key={user.id} value={user.id}>{user.name} ({user.email})</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="border-t border-gray-200 pt-4">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Employment Details</h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="employee_number" className="block text-sm font-medium text-gray-700 mb-2">
                                        Employee Number
                                    </label>
                                    <input
                                        id="employee_number"
                                        type="text"
                                        value={data.employee_number}
                                        onChange={(e) => setData('employee_number', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="hire_date" className="block text-sm font-medium text-gray-700 mb-2">
                                        Hire Date
                                    </label>
                                    <input
                                        id="hire_date"
                                        type="date"
                                        value={data.hire_date}
                                        onChange={(e) => setData('hire_date', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label htmlFor="job_title" className="block text-sm font-medium text-gray-700 mb-2">
                                        Job Title
                                    </label>
                                    <input
                                        id="job_title"
                                        type="text"
                                        value={data.job_title}
                                        onChange={(e) => setData('job_title', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="employment_type" className="block text-sm font-medium text-gray-700 mb-2">
                                        Employment Type
                                    </label>
                                    <select
                                        id="employment_type"
                                        value={data.employment_type}
                                        onChange={(e) => setData('employment_type', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                    >
                                        <option value="">Select type</option>
                                        <option value="full_time">Full Time</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="contract">Contract</option>
                                        <option value="freelance">Freelance</option>
                                    </select>
                                </div>
                            </div>

                            <div className="mt-4">
                                <label htmlFor="salary" className="block text-sm font-medium text-gray-700 mb-2">
                                    Salary
                                </label>
                                <input
                                    id="salary"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.salary}
                                    onChange={(e) => setData('salary', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="is_active"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                            />
                            <label htmlFor="is_active" className="text-sm font-medium text-gray-700">
                                Active
                            </label>
                        </div>

                        <div className="flex gap-4 pt-4 border-t border-gray-200">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Update Team Member
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => window.history.back()}
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </SectionLayout>
    );
}

