import React, { useState, useRef } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm, router, Link } from '@inertiajs/react';
import { Save, Upload, Eye, ArrowLeft, Image as ImageIcon } from 'lucide-react';

export default function EditTemplate({ template }) {
    const [preview, setPreview] = useState(null);
    const [uploadingImage, setUploadingImage] = useState(false);
    const fileInputRef = useRef(null);
    const editorRef = useRef(null);

    const { data, setData, put, processing, errors } = useForm({
        name: template.name || '',
        subject: template.subject || '',
        body: template.body || '',
        category: template.category || 'general',
        is_active: template.is_active ?? true,
        variables: template.variables || [],
    });

    const handleImageUpload = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        setUploadingImage(true);
        const formData = new FormData();
        formData.append('image', file);
        formData.append('template_slug', template.slug);

        try {
            const response = await fetch('/admin/communication/upload-image', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            });

            const result = await response.json();
            if (result.success) {
                // Insert image into editor at cursor position
                const imgTag = `<img src="${result.url}" alt="Uploaded image" style="max-width: 100%;" />`;
                const currentBody = data.body;
                const cursorPos = editorRef.current?.selectionStart || currentBody.length;
                const newBody = currentBody.slice(0, cursorPos) + imgTag + currentBody.slice(cursorPos);
                setData('body', newBody);
            }
        } catch (error) {
            console.error('Image upload failed:', error);
            alert('Failed to upload image');
        } finally {
            setUploadingImage(false);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    const handlePreview = async () => {
        try {
            const response = await fetch(`/admin/communication/templates/${template.id}/preview`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ data: {} }),
            });
            const result = await response.json();
            setPreview(result);
        } catch (error) {
            console.error('Preview failed:', error);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/communication/templates/${template.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title={`Edit Template: ${template.name}`}>
            <Head title={`Edit Template: ${template.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href="/admin/communication/templates"
                            className="text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Edit Template</h1>
                            <p className="mt-1 text-gray-600">Template: {template.slug}</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <button
                            onClick={handlePreview}
                            className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center space-x-2"
                        >
                            <Eye className="w-4 h-4" />
                            <span>Preview</span>
                        </button>
                        <button
                            onClick={handleSubmit}
                            disabled={processing}
                            className="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 flex items-center space-x-2"
                        >
                            <Save className="w-4 h-4" />
                            <span>{processing ? 'Saving...' : 'Save'}</span>
                        </button>
                    </div>
                </div>

                {/* Preview Modal */}
                {preview && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                        <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                            <div className="p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="text-xl font-bold">Preview</h2>
                                    <button
                                        onClick={() => setPreview(null)}
                                        className="text-gray-500 hover:text-gray-700"
                                    >
                                        ×
                                    </button>
                                </div>
                                <div className="border border-gray-200 rounded p-4">
                                    <div className="mb-2">
                                        <strong>Subject:</strong> {preview.subject}
                                    </div>
                                    <div
                                        className="prose max-w-none"
                                        dangerouslySetInnerHTML={{ __html: preview.body }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow">
                    <div className="p-6 space-y-6">
                        {/* Basic Info */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Template Name *
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    required
                                />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Category *
                                </label>
                                <select
                                    value={data.category}
                                    onChange={(e) => setData('category', e.target.value)}
                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    required
                                >
                                    <option value="general">General</option>
                                    <option value="onboarding">Onboarding</option>
                                    <option value="billing">Billing</option>
                                    <option value="support">Support</option>
                                    <option value="notifications">Notifications</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                />
                                <span className="text-sm text-gray-700">Template is active</span>
                            </label>
                        </div>

                        {/* Subject */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Email Subject *
                            </label>
                            <input
                                type="text"
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                placeholder="e.g., Welcome to Addy Business!"
                                required
                            />
                            <p className="mt-1 text-xs text-gray-500">
                                Use variables like {'{{user_name}}'} or {'{{organization_name}}'}
                            </p>
                            {errors.subject && <p className="mt-1 text-sm text-red-600">{errors.subject}</p>}
                        </div>

                        {/* Body Editor */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <label className="block text-sm font-medium text-gray-700">
                                    Email Body (HTML) *
                                </label>
                                <div className="flex items-center space-x-2">
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/*"
                                        onChange={handleImageUpload}
                                        className="hidden"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => fileInputRef.current?.click()}
                                        disabled={uploadingImage}
                                        className="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 flex items-center space-x-1 disabled:opacity-50"
                                    >
                                        <ImageIcon className="w-4 h-4" />
                                        <span>{uploadingImage ? 'Uploading...' : 'Insert Image'}</span>
                                    </button>
                                </div>
                            </div>
                            <textarea
                                ref={editorRef}
                                value={data.body}
                                onChange={(e) => setData('body', e.target.value)}
                                rows={20}
                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 font-mono text-sm"
                                placeholder="Enter HTML email body. Use variables like {{user_name}}, {{organization_name}}, etc."
                                required
                            />
                            <p className="mt-1 text-xs text-gray-500">
                                HTML is supported. Use variables: {'{{user_name}}'}, {'{{organization_name}}'}, etc.
                            </p>
                            {errors.body && <p className="mt-1 text-sm text-red-600">{errors.body}</p>}
                        </div>

                        {/* Variables Info */}
                        {template.variables && template.variables.length > 0 && (
                            <div className="bg-gray-50 rounded-lg p-4">
                                <h3 className="text-sm font-medium text-gray-900 mb-2">Available Variables</h3>
                                <div className="flex flex-wrap gap-2">
                                    {template.variables.map((variable) => (
                                        <code
                                            key={variable}
                                            className="px-2 py-1 bg-white border border-gray-300 rounded text-xs"
                                        >
                                            {'{{' + variable + '}}'}
                                        </code>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

