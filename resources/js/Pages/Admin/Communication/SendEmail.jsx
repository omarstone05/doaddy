import React, { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Send, Users, Building2, Mail, FileText } from 'lucide-react';

export default function SendEmail({ templates, organizations, users }) {
    const [selectedTemplate, setSelectedTemplate] = useState(null);
    const [recipientType, setRecipientType] = useState('selected_users');

    const { data, setData, post, processing, errors } = useForm({
        template_id: null,
        subject: '',
        body: '',
        recipient_type: 'selected_users',
        user_ids: [],
        organization_ids: [],
        variables: {},
    });

    const handleTemplateSelect = (templateId) => {
        const template = templates.find(t => t.id === templateId);
        if (template) {
            setSelectedTemplate(template);
            setData({
                ...data,
                template_id: templateId,
                subject: template.subject,
                body: template.body,
            });
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/communication/send', {
            preserveScroll: true,
            onSuccess: () => {
                router.visit('/admin/communication');
            },
        });
    };

    return (
        <AdminLayout title="Send Email">
            <Head title="Send Email" />

            <div className="max-w-4xl mx-auto space-y-6">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Send Email</h1>
                    <p className="mt-2 text-gray-600">Send emails to users or organizations</p>
                </div>

                <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-6">
                    {/* Template Selection */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Use Template (Optional)
                        </label>
                        <select
                            value={data.template_id || ''}
                            onChange={(e) => {
                                const templateId = e.target.value ? parseInt(e.target.value) : null;
                                if (templateId) {
                                    handleTemplateSelect(templateId);
                                } else {
                                    setSelectedTemplate(null);
                                    setData({ ...data, template_id: null });
                                }
                            }}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                            <option value="">Custom Email</option>
                            {templates.map((template) => (
                                <option key={template.id} value={template.id}>
                                    {template.name} ({template.category})
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Subject */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Subject *
                        </label>
                        <input
                            type="text"
                            value={data.subject}
                            onChange={(e) => setData('subject', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            required
                        />
                        {errors.subject && <p className="mt-1 text-sm text-red-600">{errors.subject}</p>}
                    </div>

                    {/* Body */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Email Body (HTML) *
                        </label>
                        <textarea
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                            rows={15}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 font-mono text-sm"
                            required
                        />
                        <p className="mt-1 text-xs text-gray-500">
                            HTML supported. Use variables: {'{{user_name}}'}, {'{{user_email}}'}, etc.
                        </p>
                        {errors.body && <p className="mt-1 text-sm text-red-600">{errors.body}</p>}
                    </div>

                    {/* Recipient Type */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Recipients *
                        </label>
                        <div className="space-y-3">
                            <label className="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input
                                    type="radio"
                                    value="all_users"
                                    checked={data.recipient_type === 'all_users'}
                                    onChange={(e) => {
                                        setRecipientType(e.target.value);
                                        setData('recipient_type', e.target.value);
                                    }}
                                    className="text-teal-600 focus:ring-teal-500"
                                />
                                <Users className="w-5 h-5 text-gray-400" />
                                <div>
                                    <div className="font-medium">All Users</div>
                                    <div className="text-sm text-gray-500">Send to all registered users</div>
                                </div>
                            </label>

                            <label className="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input
                                    type="radio"
                                    value="selected_users"
                                    checked={data.recipient_type === 'selected_users'}
                                    onChange={(e) => {
                                        setRecipientType(e.target.value);
                                        setData('recipient_type', e.target.value);
                                    }}
                                    className="text-teal-600 focus:ring-teal-500"
                                />
                                <Mail className="w-5 h-5 text-gray-400" />
                                <div className="flex-1">
                                    <div className="font-medium">Selected Users</div>
                                    <div className="text-sm text-gray-500">Choose specific users</div>
                                    {data.recipient_type === 'selected_users' && (
                                        <select
                                            multiple
                                            value={data.user_ids}
                                            onChange={(e) => {
                                                const selected = Array.from(e.target.selectedOptions, option => option.value);
                                                setData('user_ids', selected);
                                            }}
                                            className="mt-2 w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            size={5}
                                        >
                                            {users.map((user) => (
                                                <option key={user.id} value={user.id}>
                                                    {user.name} ({user.email})
                                                </option>
                                            ))}
                                        </select>
                                    )}
                                </div>
                            </label>

                            <label className="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input
                                    type="radio"
                                    value="selected_organizations"
                                    checked={data.recipient_type === 'selected_organizations'}
                                    onChange={(e) => {
                                        setRecipientType(e.target.value);
                                        setData('recipient_type', e.target.value);
                                    }}
                                    className="text-teal-600 focus:ring-teal-500"
                                />
                                <Building2 className="w-5 h-5 text-gray-400" />
                                <div className="flex-1">
                                    <div className="font-medium">Selected Organizations</div>
                                    <div className="text-sm text-gray-500">Send to all users in selected organizations</div>
                                    {data.recipient_type === 'selected_organizations' && (
                                        <select
                                            multiple
                                            value={data.organization_ids}
                                            onChange={(e) => {
                                                const selected = Array.from(e.target.selectedOptions, option => option.value);
                                                setData('organization_ids', selected);
                                            }}
                                            className="mt-2 w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            size={5}
                                        >
                                            {organizations.map((org) => (
                                                <option key={org.id} value={org.id}>
                                                    {org.name}
                                                </option>
                                            ))}
                                        </select>
                                    )}
                                </div>
                            </label>

                            <label className="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input
                                    type="radio"
                                    value="all_organizations"
                                    checked={data.recipient_type === 'all_organizations'}
                                    onChange={(e) => {
                                        setRecipientType(e.target.value);
                                        setData('recipient_type', e.target.value);
                                    }}
                                    className="text-teal-600 focus:ring-teal-500"
                                />
                                <Building2 className="w-5 h-5 text-gray-400" />
                                <div>
                                    <div className="font-medium">All Organizations</div>
                                    <div className="text-sm text-gray-500">Send to all users in all active organizations</div>
                                </div>
                            </label>
                        </div>
                        {errors.recipient_type && <p className="mt-1 text-sm text-red-600">{errors.recipient_type}</p>}
                    </div>

                    {/* Submit */}
                    <div className="flex items-center justify-end space-x-4 pt-4 border-t">
                        <button
                            type="button"
                            onClick={() => router.visit('/admin/communication')}
                            className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 flex items-center space-x-2"
                        >
                            <Send className="w-4 h-4" />
                            <span>{processing ? 'Sending...' : 'Send Email'}</span>
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

