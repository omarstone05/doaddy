import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Edit, User, Building2, DollarSign, Link as LinkIcon, Plus, Trash2, ExternalLink, FileText, Eye, X, Download } from 'lucide-react';
import { useState } from 'react';
import axios from 'axios';

export default function TeamShow({ teamMember }) {
    const [showAddLink, setShowAddLink] = useState(false);
    const [linkName, setLinkName] = useState('');
    const [linkUrl, setLinkUrl] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [attachments, setAttachments] = useState(teamMember.attachments || []);
    const [previewDocument, setPreviewDocument] = useState(null);
    const documents = teamMember.documents || [];

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const handleAddLink = async (e) => {
        e.preventDefault();
        if (!linkName.trim() || !linkUrl.trim()) return;

        setIsSubmitting(true);
        try {
            const response = await axios.post('/api/addy/attachments', {
                name: linkName,
                url: linkUrl,
                attachable_type: 'App\\Models\\TeamMember',
                attachable_id: teamMember.id,
                category: 'employee_link',
            });

            setAttachments([response.data.attachment, ...attachments]);
            setLinkName('');
            setLinkUrl('');
            setShowAddLink(false);
        } catch (error) {
            console.error('Error adding link:', error);
            alert('Failed to add link. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDeleteLink = async (attachmentId) => {
        if (!confirm('Are you sure you want to delete this link?')) return;

        try {
            await axios.delete(`/api/addy/attachments/${attachmentId}`);
            setAttachments(attachments.filter(a => a.id !== attachmentId));
        } catch (error) {
            console.error('Error deleting link:', error);
            alert('Failed to delete link. Please try again.');
        }
    };

    const handlePreviewDocument = (document) => {
        setPreviewDocument(document);
    };

    const handleDownloadDocument = (document) => {
        if (document.type === 'link' && document.description) {
            window.open(document.description.replace('Link: ', ''), '_blank');
            return;
        }
        
        // If document has attachments, download the first one
        if (document.attachments && document.attachments.length > 0) {
            const attachment = document.attachments[0];
            if (attachment.url) {
                window.open(attachment.url, '_blank');
            } else if (attachment.id) {
                // Check if it's a file attachment or link
                if (attachment.file_path) {
                    window.open(`/api/addy/attachments/${attachment.id}/download`, '_blank');
                } else if (attachment.url) {
                    window.open(attachment.url, '_blank');
                }
            }
        } else {
            // Fallback to document show page
            router.visit(`/compliance/documents/${document.id}`);
        }
    };

    const getDocumentIcon = (document) => {
        if (document.type === 'link') {
            return <LinkIcon className="h-5 w-5 text-teal-600" />;
        }
        return <FileText className="h-5 w-5 text-teal-600" />;
    };

    const canPreview = (document) => {
        if (document.type === 'link') return false;
        if (document.attachments && document.attachments.length > 0) {
            const attachment = document.attachments[0];
            const mimeType = attachment.mime_type || '';
            return mimeType.startsWith('image/') || mimeType === 'application/pdf';
        }
        return false;
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

                {/* Links & Attachments */}
                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xl font-semibold text-gray-900">Links & Attachments</h2>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowAddLink(!showAddLink)}
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            Add Link
                        </Button>
                    </div>

                    {showAddLink && (
                        <form onSubmit={handleAddLink} className="mb-4 p-4 bg-gray-50 rounded-lg">
                            <div className="space-y-3">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Link Name
                                    </label>
                                    <input
                                        type="text"
                                        value={linkName}
                                        onChange={(e) => setLinkName(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                                        placeholder="e.g., LinkedIn Profile, Portfolio"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        URL
                                    </label>
                                    <input
                                        type="url"
                                        value={linkUrl}
                                        onChange={(e) => setLinkUrl(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                                        placeholder="https://example.com"
                                        required
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit" disabled={isSubmitting}>
                                        {isSubmitting ? 'Adding...' : 'Add Link'}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => {
                                            setShowAddLink(false);
                                            setLinkName('');
                                            setLinkUrl('');
                                        }}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </form>
                    )}

                    {attachments.length > 0 ? (
                        <div className="space-y-2">
                            {attachments.map((attachment) => (
                                <div
                                    key={attachment.id}
                                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <div className="flex items-center gap-3 flex-1">
                                        <LinkIcon className="h-5 w-5 text-teal-600" />
                                        <div className="flex-1 min-w-0">
                                            <div className="font-medium text-gray-900 truncate">
                                                {attachment.name}
                                            </div>
                                            {attachment.url && (
                                                <a
                                                    href={attachment.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-sm text-teal-600 hover:text-teal-700 truncate block"
                                                >
                                                    {attachment.url}
                                                </a>
                                            )}
                                            {attachment.uploaded_by && (
                                                <div className="text-xs text-gray-500 mt-1">
                                                    Added by {attachment.uploaded_by.name} • {new Date(attachment.created_at).toLocaleDateString()}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {attachment.url && (
                                            <a
                                                href={attachment.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="p-2 text-gray-600 hover:text-teal-600"
                                            >
                                                <ExternalLink className="h-4 w-4" />
                                            </a>
                                        )}
                                        <button
                                            onClick={() => handleDeleteLink(attachment.id)}
                                            className="p-2 text-gray-600 hover:text-red-600"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-4">No links or attachments yet.</p>
                    )}
                </div>

                {/* Documents Section */}
                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xl font-semibold text-gray-900">Documents</h2>
                    </div>

                    {documents.length > 0 ? (
                        <div className="space-y-2">
                            {documents.map((document) => (
                                <div
                                    key={document.id}
                                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <div className="flex items-center gap-3 flex-1 min-w-0">
                                        {getDocumentIcon(document)}
                                        <div className="flex-1 min-w-0">
                                            <div className="font-medium text-gray-900 truncate">
                                                {document.name}
                                            </div>
                                            {document.description && (
                                                <p className="text-sm text-gray-500 truncate">
                                                    {document.type === 'link' 
                                                        ? document.description.replace('Link: ', '')
                                                        : document.description
                                                    }
                                                </p>
                                            )}
                                            {document.category && (
                                                <span className="inline-block mt-1 px-2 py-0.5 text-xs font-medium text-gray-600 bg-gray-200 rounded">
                                                    {document.category}
                                                </span>
                                            )}
                                            {document.created_by && (
                                                <div className="text-xs text-gray-500 mt-1">
                                                    Created by {document.created_by.name} • {new Date(document.created_at).toLocaleDateString()}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {canPreview(document) && (
                                            <button
                                                onClick={() => handlePreviewDocument(document)}
                                                className="p-2 text-gray-600 hover:text-teal-600"
                                                title="Preview"
                                            >
                                                <Eye className="h-4 w-4" />
                                            </button>
                                        )}
                                        <button
                                            onClick={() => handleDownloadDocument(document)}
                                            className="p-2 text-gray-600 hover:text-teal-600"
                                            title="Download/View"
                                        >
                                            {document.type === 'link' ? (
                                                <ExternalLink className="h-4 w-4" />
                                            ) : (
                                                <Download className="h-4 w-4" />
                                            )}
                                        </button>
                                        <Link
                                            href={`/compliance/documents/${document.id}`}
                                            className="p-2 text-gray-600 hover:text-teal-600"
                                            title="View Details"
                                        >
                                            <FileText className="h-4 w-4" />
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-4">No documents assigned to this team member.</p>
                    )}
                </div>

                {/* Document Preview Modal */}
                {previewDocument && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                        <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] flex flex-col">
                            <div className="flex items-center justify-between p-4 border-b border-gray-200">
                                <h3 className="text-lg font-semibold text-gray-900">{previewDocument.name}</h3>
                                <button
                                    onClick={() => setPreviewDocument(null)}
                                    className="p-2 text-gray-600 hover:text-gray-900"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            <div className="flex-1 overflow-auto p-4">
                                {previewDocument.attachments && previewDocument.attachments.length > 0 ? (
                                    (() => {
                                        const attachment = previewDocument.attachments[0];
                                        const mimeType = attachment.mime_type || '';
                                        
                                        // Get the file URL
                                        const fileUrl = attachment.url || (attachment.file_path ? `/storage/${attachment.file_path}` : null);
                                        
                                        if (!fileUrl) {
                                            return (
                                                <div className="text-center py-8">
                                                    <FileText className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                                                    <p className="text-gray-600 mb-4">File not available for preview.</p>
                                                    <Button onClick={() => handleDownloadDocument(previewDocument)}>
                                                        <Download className="h-4 w-4 mr-2" />
                                                        Download to View
                                                    </Button>
                                                </div>
                                            );
                                        }
                                        
                                        if (mimeType.startsWith('image/')) {
                                            return (
                                                <div className="flex items-center justify-center">
                                                    <img
                                                        src={fileUrl}
                                                        alt={previewDocument.name}
                                                        className="max-w-full h-auto max-h-[70vh]"
                                                        onError={(e) => {
                                                            e.target.style.display = 'none';
                                                            e.target.nextSibling.style.display = 'block';
                                                        }}
                                                    />
                                                    <div style={{ display: 'none' }} className="text-center py-8">
                                                        <FileText className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                                                        <p className="text-gray-600 mb-4">Image could not be loaded.</p>
                                                        <Button onClick={() => handleDownloadDocument(previewDocument)}>
                                                            <Download className="h-4 w-4 mr-2" />
                                                            Download to View
                                                        </Button>
                                                    </div>
                                                </div>
                                            );
                                        } else if (mimeType === 'application/pdf') {
                                            return (
                                                <iframe
                                                    src={fileUrl}
                                                    className="w-full h-[600px] border-0"
                                                    title={previewDocument.name}
                                                />
                                            );
                                        } else {
                                            return (
                                                <div className="text-center py-8">
                                                    <FileText className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                                                    <p className="text-gray-600 mb-4">Preview not available for this file type.</p>
                                                    <Button onClick={() => handleDownloadDocument(previewDocument)}>
                                                        <Download className="h-4 w-4 mr-2" />
                                                        Download to View
                                                    </Button>
                                                </div>
                                            );
                                        }
                                    })()
                                ) : (
                                    <div className="text-center py-8">
                                        <FileText className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                                        <p className="text-gray-600 mb-4">No file attached to this document.</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

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

