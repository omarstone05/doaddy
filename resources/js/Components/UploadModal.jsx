import { useState, useRef, useEffect } from 'react';
import { X, Upload, FileText, Image, CheckCircle2, AlertCircle, Loader } from 'lucide-react';
import { Button } from './ui/Button';
import { Card } from './ui/Card';
import axios from 'axios';
import { router } from '@inertiajs/react';

export default function UploadModal({ isOpen, onClose, onSuccess, context = 'transactions' }) {
    const [selectedFiles, setSelectedFiles] = useState([]);
    const [uploadProgress, setUploadProgress] = useState({});
    const [uploadStatus, setUploadStatus] = useState({});
    const [isUploading, setIsUploading] = useState(false);
    const [reviewData, setReviewData] = useState(null);
    const fileInputRef = useRef(null);

    const acceptedTypes = {
        csv: ['.csv'],
        image: ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.pdf']
    };

    const handleFileSelect = (e) => {
        const files = Array.from(e.target.files);
        const validFiles = files.filter(file => {
            const ext = '.' + file.name.split('.').pop().toLowerCase();
            return acceptedTypes.csv.includes(ext) || acceptedTypes.image.includes(ext);
        });

        if (validFiles.length !== files.length) {
            alert('Some files were rejected. Only CSV and image files (JPG, PNG, GIF, WEBP, PDF) are allowed.');
        }

        setSelectedFiles(prev => [...prev, ...validFiles]);
    };

    const removeFile = (index) => {
        setSelectedFiles(prev => prev.filter((_, i) => i !== index));
        setUploadProgress(prev => {
            const newProgress = { ...prev };
            delete newProgress[index];
            return newProgress;
        });
        setUploadStatus(prev => {
            const newStatus = { ...prev };
            delete newStatus[index];
            return newStatus;
        });
    };

    const getFileType = (file) => {
        const ext = '.' + file.name.split('.').pop().toLowerCase();
        if (acceptedTypes.csv.includes(ext)) return 'csv';
        if (acceptedTypes.image.includes(ext)) return 'image';
        return 'unknown';
    };

    const previewCSV = async (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                const lines = text.split('\n').filter(line => line.trim());
                const headers = lines[0]?.split(',').map(h => h.trim()) || [];
                const previewRows = lines.slice(1, 6).map(line => {
                    const values = line.split(',').map(v => v.trim());
                    return headers.reduce((obj, header, idx) => {
                        obj[header] = values[idx] || '';
                        return obj;
                    }, {});
                });
                resolve({
                    headers,
                    previewRows,
                    totalRows: lines.length - 1
                });
            };
            reader.onerror = reject;
            reader.readAsText(file);
        });
    };

    const previewImage = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    };

    const handleReview = async () => {
        if (selectedFiles.length === 0) return;

        const reviews = await Promise.all(
            selectedFiles.map(async (file, index) => {
                const type = getFileType(file);
                let preview = null;

                if (type === 'csv') {
                    try {
                        preview = await previewCSV(file);
                    } catch (error) {
                        console.error('Failed to preview CSV:', error);
                    }
                } else if (type === 'image') {
                    try {
                        preview = await previewImage(file);
                    } catch (error) {
                        console.error('Failed to preview image:', error);
                    }
                }

                return {
                    index,
                    file,
                    type,
                    preview,
                    name: file.name,
                    size: file.size,
                };
            })
        );

        setReviewData(reviews);
    };

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    const handleUpload = async () => {
        if (selectedFiles.length === 0) return;

        setIsUploading(true);
        const results = [];

        for (let i = 0; i < selectedFiles.length; i++) {
            const file = selectedFiles[i];
            const type = getFileType(file);
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);
            formData.append('context', context);

            setUploadStatus(prev => ({ ...prev, [i]: 'uploading' }));
            setUploadProgress(prev => ({ ...prev, [i]: 0 }));

            try {
                const response = await axios.post('/api/upload', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                    onUploadProgress: (progressEvent) => {
                        const percentCompleted = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );
                        setUploadProgress(prev => ({ ...prev, [i]: percentCompleted }));
                    },
                });

                setUploadStatus(prev => ({ ...prev, [i]: 'success' }));
                results.push({ success: true, file: file.name, data: response.data });
            } catch (error) {
                setUploadStatus(prev => ({ ...prev, [i]: 'error' }));
                results.push({ success: false, file: file.name, error: error.response?.data?.message || error.message });
            }
        }

        setIsUploading(false);

        const successCount = results.filter(r => r.success).length;
        if (successCount === selectedFiles.length) {
            if (onSuccess) {
                onSuccess(results);
            }
            // Auto-close after 2 seconds
            setTimeout(() => {
                handleClose();
                if (context === 'transactions') {
                    router.reload();
                }
            }, 2000);
        } else {
            alert(`${successCount} of ${selectedFiles.length} files uploaded successfully. Check the results below.`);
        }
    };

    const handleClose = () => {
        setSelectedFiles([]);
        setUploadProgress({});
        setUploadStatus({});
        setReviewData(null);
        setIsUploading(false);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        onClose();
    };

    if (!isOpen) return null;

    return (
        <>
            {/* Backdrop with blur */}
            <div className="fixed inset-0 bg-gradient-to-br from-teal-50/30 via-mint-50/20 to-white/40 backdrop-blur-md z-50" onClick={onClose} />
            
            {/* Modal Container */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div className="bg-white/90 backdrop-blur-2xl rounded-3xl shadow-2xl border border-white/60 max-w-3xl w-full max-h-[90vh] overflow-y-auto relative" style={{
                    background: 'linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(240,253,250,0.9) 50%, rgba(255,255,255,0.95) 100%)'
                }}>
                {/* Header */}
                <div className="sticky top-0 bg-white/40 backdrop-blur-md border-b border-mint-200/40 px-6 py-4 flex items-center justify-between z-10 rounded-t-3xl" style={{
                    background: 'linear-gradient(180deg, rgba(255,255,255,0.6) 0%, rgba(240,253,250,0.4) 100%)'
                }}>
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm shadow-md flex items-center justify-center border border-mint-200/30">
                            <Upload className="h-5 w-5 text-teal-500" />
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-teal-700">Upload Files</h3>
                            <p className="text-xs text-teal-600/70">CSV files or Images</p>
                        </div>
                    </div>
                    <button
                        onClick={handleClose}
                        className="p-2 rounded-xl bg-white/60 hover:bg-white/80 backdrop-blur-sm border border-mint-200/50 text-teal-700 hover:text-teal-800 transition-all shadow-sm"
                        disabled={isUploading}
                        title="Close"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <div className="p-6 space-y-6" style={{
                    background: 'linear-gradient(180deg, rgba(255,255,255,0.5) 0%, rgba(240,253,250,0.3) 50%, rgba(255,255,255,0.5) 100%)'
                }}>
                    {/* File Selection */}
                    {!reviewData && (
                        <>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Select Files (CSV or Images)
                                </label>
                                <div className="border-2 border-dashed border-mint-200/50 rounded-2xl p-8 text-center hover:border-teal-400/70 transition-all bg-white/40 backdrop-blur-sm hover:bg-white/60">
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        multiple
                                        accept=".csv,.jpg,.jpeg,.png,.gif,.webp,.pdf"
                                        onChange={handleFileSelect}
                                        className="hidden"
                                        id="file-upload"
                                    />
                                    <label
                                        htmlFor="file-upload"
                                        className="cursor-pointer flex flex-col items-center"
                                    >
                                        <div className="w-16 h-16 rounded-full bg-gradient-to-br from-teal-500/20 to-mint-400/20 backdrop-blur-sm flex items-center justify-center mb-4 border border-teal-200/30">
                                            <Upload className="h-8 w-8 text-teal-600" />
                                        </div>
                                        <span className="text-sm font-semibold text-teal-700 mb-1">
                                            Click to upload or drag and drop
                                        </span>
                                        <span className="text-xs text-teal-600/70">
                                            CSV files or Images (JPG, PNG, GIF, WEBP, PDF)
                                        </span>
                                    </label>
                                </div>
                            </div>

                            {/* Selected Files List */}
                            {selectedFiles.length > 0 && (
                                <div>
                                    <h4 className="text-sm font-medium text-gray-700 mb-3">
                                        Selected Files ({selectedFiles.length})
                                    </h4>
                                    <div className="space-y-2">
                                        {selectedFiles.map((file, index) => (
                                            <Card key={index} className="p-3 flex items-center justify-between bg-white/60 backdrop-blur-sm border border-mint-200/50 rounded-xl">
                                                <div className="flex items-center gap-3 flex-1">
                                                    {getFileType(file) === 'csv' ? (
                                                        <div className="w-10 h-10 rounded-lg bg-blue-100/50 flex items-center justify-center border border-blue-200/30">
                                                            <FileText className="h-5 w-5 text-blue-600" />
                                                        </div>
                                                    ) : (
                                                        <div className="w-10 h-10 rounded-lg bg-green-100/50 flex items-center justify-center border border-green-200/30">
                                                            <Image className="h-5 w-5 text-green-600" />
                                                        </div>
                                                    )}
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-gray-900 truncate">
                                                            {file.name}
                                                        </p>
                                                        <p className="text-xs text-teal-600/70">
                                                            {formatFileSize(file.size)} • {getFileType(file).toUpperCase()}
                                                        </p>
                                                    </div>
                                                </div>
                                                <button
                                                    onClick={() => removeFile(index)}
                                                    className="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50/50 ml-4 transition-colors"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            </Card>
                                        ))}
                                    </div>
                                    <div className="mt-4 flex gap-2">
                                        <Button onClick={handleReview} className="flex-1 bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 shadow-lg">
                                            Review Files
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}

                    {/* Review Section */}
                    {reviewData && !isUploading && (
                        <>
                            <div>
                                <h4 className="text-lg font-semibold text-gray-900 mb-4">Review Files</h4>
                                <div className="space-y-4">
                                    {reviewData.map((item) => (
                                        <Card key={item.index} className="p-4 bg-white/60 backdrop-blur-sm border border-mint-200/50 rounded-xl">
                                            <div className="flex items-start justify-between mb-3">
                                                <div className="flex items-center gap-3">
                                                    {item.type === 'csv' ? (
                                                        <FileText className="h-5 w-5 text-blue-500" />
                                                    ) : (
                                                        <Image className="h-5 w-5 text-green-500" />
                                                    )}
                                                    <div>
                                                        <p className="font-medium text-gray-900">{item.name}</p>
                                                        <p className="text-sm text-gray-500">{formatFileSize(item.size)}</p>
                                                    </div>
                                                </div>
                                                {uploadStatus[item.index] === 'success' && (
                                                    <CheckCircle2 className="h-5 w-5 text-green-500" />
                                                )}
                                                {uploadStatus[item.index] === 'error' && (
                                                    <AlertCircle className="h-5 w-5 text-red-500" />
                                                )}
                                            </div>

                                            {item.type === 'csv' && item.preview && (
                                                <div className="mt-3 border border-gray-200 rounded-lg overflow-hidden">
                                                    <div className="bg-gray-50 px-3 py-2 text-xs font-medium text-gray-700 border-b">
                                                        Preview ({item.preview.totalRows} total rows)
                                                    </div>
                                                    <div className="overflow-x-auto">
                                                        <table className="w-full text-xs">
                                                            <thead className="bg-gray-50">
                                                                <tr>
                                                                    {item.preview.headers.map((header, idx) => (
                                                                        <th key={idx} className="px-2 py-1 text-left font-medium text-gray-700">
                                                                            {header}
                                                                        </th>
                                                                    ))}
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {item.preview.previewRows.map((row, rowIdx) => (
                                                                    <tr key={rowIdx} className="border-t">
                                                                        {item.preview.headers.map((header, colIdx) => (
                                                                            <td key={colIdx} className="px-2 py-1 text-gray-600">
                                                                                {row[header] || '-'}
                                                                            </td>
                                                                        ))}
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            )}

                                            {item.type === 'image' && item.preview && (
                                                <div className="mt-3">
                                                    <img
                                                        src={item.preview}
                                                        alt={item.name}
                                                        className="max-w-full h-48 object-contain rounded-lg border border-gray-200"
                                                    />
                                                </div>
                                            )}

                                            {/* Upload Progress */}
                                            {uploadStatus[item.index] === 'uploading' && (
                                                <div className="mt-3">
                                                    <div className="flex items-center justify-between text-xs text-gray-600 mb-1">
                                                        <span>Uploading...</span>
                                                        <span>{uploadProgress[item.index] || 0}%</span>
                                                    </div>
                                                    <div className="w-full bg-gray-200 rounded-full h-2">
                                                        <div
                                                            className="bg-teal-500 h-2 rounded-full transition-all duration-300"
                                                            style={{ width: `${uploadProgress[item.index] || 0}%` }}
                                                        />
                                                    </div>
                                                </div>
                                            )}
                                        </Card>
                                    ))}
                                </div>
                            </div>

                            <div className="flex gap-2 pt-4 border-t border-mint-200/40">
                                <Button
                                    variant="outline"
                                    onClick={() => setReviewData(null)}
                                    disabled={isUploading}
                                    className="flex-1 bg-white/60 backdrop-blur-sm border-mint-200/50 hover:bg-white/80 text-teal-700"
                                >
                                    Back
                                </Button>
                                <Button
                                    onClick={handleUpload}
                                    disabled={isUploading || Object.values(uploadStatus).some(s => s === 'uploading')}
                                    className="flex-1 bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 shadow-lg"
                                >
                                    {isUploading ? (
                                        <>
                                            <Loader className="h-4 w-4 mr-2 animate-spin" />
                                            Uploading...
                                        </>
                                    ) : (
                                        <>
                                            <Upload className="h-4 w-4 mr-2" />
                                            Upload All
                                        </>
                                    )}
                                </Button>
                            </div>
                        </>
                    )}

                    {/* Upload Complete */}
                    {isUploading === false && reviewData && Object.values(uploadStatus).every(s => s === 'success' || s === 'error') && (
                        <div className="text-center py-4">
                            <div className="w-16 h-16 rounded-full bg-green-100/50 flex items-center justify-center mx-auto mb-3 border border-green-200/30">
                                <CheckCircle2 className="h-8 w-8 text-green-600" />
                            </div>
                            <p className="text-sm font-semibold text-teal-700">Upload complete!</p>
                        </div>
                    )}
                </div>
                </div>
            </div>
        </>
    );
}

