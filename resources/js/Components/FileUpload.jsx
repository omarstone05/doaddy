import React, { useState, useRef } from 'react';
import axios from 'axios';
import { Paperclip, X, Download, Trash2, FileText, Image as ImageIcon } from 'lucide-react';

export default function FileUpload({ 
    attachableType, 
    attachableId, 
    category = null,
    onUploadComplete = null,
    onDelete = null,
    existingAttachments = [],
    maxFiles = 5,
    maxSizeMB = 10
}) {
    const [selectedFiles, setSelectedFiles] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [attachments, setAttachments] = useState(existingAttachments || []);
    const fileInputRef = useRef(null);

    const handleFileSelect = (e) => {
        const files = Array.from(e.target.files);
        const validFiles = files.filter(file => {
            const maxSize = maxSizeMB * 1024 * 1024;
            const allowedTypes = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain'
            ];
            
            if (file.size > maxSize) {
                alert(`File ${file.name} is too large. Maximum size is ${maxSizeMB}MB.`);
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                alert(`File ${file.name} is not a supported type.`);
                return false;
            }
            
            return true;
        });
        
        if (validFiles.length + selectedFiles.length > maxFiles) {
            alert(`Maximum ${maxFiles} files allowed.`);
            return;
        }
        
        setSelectedFiles(prev => [...prev, ...validFiles]);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const removeSelectedFile = (index) => {
        setSelectedFiles(prev => prev.filter((_, i) => i !== index));
    };

    const handleUpload = async () => {
        if (selectedFiles.length === 0 || !attachableType || !attachableId) return;

        setUploading(true);
        const uploadPromises = selectedFiles.map(async (file) => {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('attachable_type', attachableType);
            formData.append('attachable_id', attachableId);
            if (category) {
                formData.append('category', category);
            }

            try {
                const response = await axios.post('/api/addy/attachments', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                return response.data.attachment;
            } catch (error) {
                console.error('Upload failed:', error);
                alert(`Failed to upload ${file.name}: ${error.response?.data?.message || error.message}`);
                return null;
            }
        });

        const results = await Promise.all(uploadPromises);
        const successful = results.filter(r => r !== null);
        
        setAttachments(prev => [...prev, ...successful]);
        setSelectedFiles([]);
        setUploading(false);
        
        if (onUploadComplete) {
            onUploadComplete(successful);
        }
    };

    const handleDelete = async (attachmentId) => {
        if (!confirm('Are you sure you want to delete this attachment?')) return;

        try {
            await axios.delete(`/api/addy/attachments/${attachmentId}`);
            setAttachments(prev => prev.filter(a => a.id !== attachmentId));
            if (onDelete) {
                onDelete(attachmentId);
            }
        } catch (error) {
            console.error('Delete failed:', error);
            alert('Failed to delete attachment');
        }
    };

    const handleDownload = (attachmentId, fileName) => {
        window.open(`/api/addy/attachments/${attachmentId}/download`, '_blank');
    };

    const getFileIcon = (mimeType) => {
        if (mimeType?.startsWith('image/')) {
            return <ImageIcon className="w-5 h-5 text-teal-600" />;
        }
        return <FileText className="w-5 h-5 text-teal-600" />;
    };

    const formatFileSize = (bytes) => {
        if (!bytes) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        return `${size.toFixed(1)} ${units[unitIndex]}`;
    };

    return (
        <div className="space-y-4">
            {/* Upload Section */}
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Attachments
                </label>
                <div className="flex gap-2">
                    <input
                        ref={fileInputRef}
                        type="file"
                        multiple
                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                        onChange={handleFileSelect}
                        className="hidden"
                        disabled={uploading || !attachableId}
                    />
                    <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        disabled={uploading || !attachableId || attachments.length + selectedFiles.length >= maxFiles}
                        className="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 text-sm font-medium text-gray-700"
                    >
                        <Paperclip className="w-4 h-4" />
                        Attach Files
                    </button>
                    {selectedFiles.length > 0 && (
                        <button
                            type="button"
                            onClick={handleUpload}
                            disabled={uploading}
                            className="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
                        >
                            {uploading ? 'Uploading...' : `Upload ${selectedFiles.length} file(s)`}
                        </button>
                    )}
                </div>
                {!attachableId && (
                    <p className="mt-1 text-xs text-gray-500">
                        Save the record first to attach files
                    </p>
                )}
            </div>

            {/* Selected Files Preview */}
            {selectedFiles.length > 0 && (
                <div className="space-y-2">
                    {selectedFiles.map((file, index) => (
                        <div key={index} className="flex items-center gap-2 p-2 bg-gray-50 border border-gray-200 rounded-lg">
                            {getFileIcon(file.type)}
                            <span className="text-sm text-gray-700 flex-1 truncate">{file.name}</span>
                            <span className="text-xs text-gray-500">{formatFileSize(file.size)}</span>
                            <button
                                type="button"
                                onClick={() => removeSelectedFile(index)}
                                className="p-1 text-red-600 hover:text-red-700 rounded"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                    ))}
                </div>
            )}

            {/* Existing Attachments */}
            {attachments.length > 0 && (
                <div className="space-y-2">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                        Attached Files ({attachments.length})
                    </label>
                    {attachments.map((attachment) => (
                        <div key={attachment.id} className="flex items-center gap-2 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                            {getFileIcon(attachment.mime_type)}
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-medium text-gray-900 truncate">
                                    {attachment.name || attachment.file_name}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {formatFileSize(attachment.file_size)} • 
                                    {attachment.uploaded_by && ` Uploaded by ${attachment.uploaded_by?.name || 'Unknown'}`}
                                </p>
                            </div>
                            <div className="flex gap-1">
                                <button
                                    type="button"
                                    onClick={() => handleDownload(attachment.id, attachment.file_name)}
                                    className="p-2 text-teal-600 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors"
                                    title="Download"
                                >
                                    <Download className="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleDelete(attachment.id)}
                                    className="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Delete"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

