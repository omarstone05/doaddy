import React, { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { FileText, Edit, Eye, CheckCircle, XCircle, Search } from 'lucide-react';

export default function Templates({ templates, categories }) {
    const [search, setSearch] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('all');

    const filteredTemplates = Object.entries(templates).filter(([category]) => {
        if (selectedCategory !== 'all' && category !== selectedCategory) return false;
        return true;
    }).reduce((acc, [category, items]) => {
        const filtered = items.filter(t => 
            t.name.toLowerCase().includes(search.toLowerCase()) ||
            t.subject.toLowerCase().includes(search.toLowerCase())
        );
        if (filtered.length > 0) {
            acc[category] = filtered;
        }
        return acc;
    }, {});

    return (
        <AdminLayout title="Email Templates">
            <Head title="Email Templates" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Email Templates</h1>
                        <p className="mt-2 text-gray-600">Manage your email templates</p>
                    </div>
                    <Link
                        href="/admin/communication/send"
                        className="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
                    >
                        Send Email
                    </Link>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-lg shadow p-4">
                    <div className="flex flex-col md:flex-row gap-4">
                        <div className="flex-1">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                <input
                                    type="text"
                                    placeholder="Search templates..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                        <div>
                            <select
                                value={selectedCategory}
                                onChange={(e) => setSelectedCategory(e.target.value)}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="all">All Categories</option>
                                {categories.map(cat => (
                                    <option key={cat} value={cat}>{cat}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                {/* Templates by Category */}
                {Object.keys(filteredTemplates).length > 0 ? (
                    Object.entries(filteredTemplates).map(([category, items]) => (
                        <div key={category} className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h2 className="text-lg font-semibold text-gray-900 capitalize">{category}</h2>
                            </div>
                            <div className="divide-y divide-gray-200">
                                {items.map((template) => (
                                    <div key={template.id} className="px-6 py-4 hover:bg-gray-50">
                                        <div className="flex items-center justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center space-x-3">
                                                    <FileText className="w-5 h-5 text-gray-400" />
                                                    <div>
                                                        <h3 className="font-medium text-gray-900">{template.name}</h3>
                                                        <p className="text-sm text-gray-600 mt-1">{template.subject}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-4">
                                                {template.is_active ? (
                                                    <span className="flex items-center text-green-600 text-sm">
                                                        <CheckCircle className="w-4 h-4 mr-1" />
                                                        Active
                                                    </span>
                                                ) : (
                                                    <span className="flex items-center text-gray-400 text-sm">
                                                        <XCircle className="w-4 h-4 mr-1" />
                                                        Inactive
                                                    </span>
                                                )}
                                                <Link
                                                    href={`/admin/communication/templates/${template.id}/edit`}
                                                    className="px-3 py-1 text-sm text-teal-600 hover:text-teal-700 hover:bg-teal-50 rounded"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))
                ) : (
                    <div className="bg-white rounded-lg shadow p-12 text-center">
                        <FileText className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-600">No templates found</p>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

