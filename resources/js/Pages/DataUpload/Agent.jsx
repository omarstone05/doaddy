import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Upload, CheckCircle, XCircle, Loader, Clock, AlertCircle } from 'lucide-react';

const AgentDataUpload = ({ recentJobs = [] }) => {
  const [uploadStatus, setUploadStatus] = useState(null);
  const [processingJobs, setProcessingJobs] = useState({});
  const { data, setData, post, processing } = useForm({
    file: null,
    is_historical: false,
  });

  // Poll for job status
  const pollJobStatus = async (jobId) => {
    try {
      const response = await fetch(`/data-upload/status/${jobId}`);
      const status = await response.json();

      setProcessingJobs(prev => ({
        ...prev,
        [jobId]: status,
      }));

      // Continue polling if not complete
      if (!status.is_complete && !status.is_failed) {
        setTimeout(() => pollJobStatus(jobId), 2000);
      }
    } catch (error) {
      console.error('Failed to poll status:', error);
    }
  };

  // Handle file upload
  const handleUpload = async (e) => {
    e.preventDefault();

    if (!data.file) {
      return;
    }

    const formData = new FormData();
    formData.append('file', data.file);
    formData.append('is_historical', data.is_historical ? '1' : '0');

    try {
      const response = await fetch('/data-upload/upload', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
      });

      const result = await response.json();

      if (result.success) {
        setUploadStatus({
          type: 'success',
          message: result.message,
          jobId: result.job_id,
        });

        // Start polling
        pollJobStatus(result.job_id);

        // Reset form
        setData('file', null);
      }
    } catch (error) {
      setUploadStatus({
        type: 'error',
        message: 'Upload failed. Please try again.',
      });
    }
  };

  // Get status icon and color
  const getStatusDisplay = (status) => {
    const displays = {
      pending: {
        icon: Clock,
        color: 'text-gray-500',
        bgColor: 'bg-gray-100',
        label: 'Queued',
      },
      extracting: {
        icon: Loader,
        color: 'text-blue-500',
        bgColor: 'bg-blue-100',
        label: 'Extracting...',
        animate: true,
      },
      analyzing: {
        icon: Loader,
        color: 'text-blue-500',
        bgColor: 'bg-blue-100',
        label: 'Analyzing...',
        animate: true,
      },
      validating: {
        icon: Loader,
        color: 'text-blue-500',
        bgColor: 'bg-blue-100',
        label: 'Validating...',
        animate: true,
      },
      importing: {
        icon: Loader,
        color: 'text-green-500',
        bgColor: 'bg-green-100',
        label: 'Importing...',
        animate: true,
      },
      completed: {
        icon: CheckCircle,
        color: 'text-green-500',
        bgColor: 'bg-green-100',
        label: 'Completed',
      },
      failed: {
        icon: XCircle,
        color: 'text-red-500',
        bgColor: 'bg-red-100',
        label: 'Failed',
      },
    };

    return displays[status] || displays.pending;
  };

  return (
    <div className="max-w-6xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">
          Smart Document Upload
        </h1>
        <p className="text-gray-600">
          Upload your documents and our AI agent will process them in the background
        </p>
      </div>

      {/* Upload Section */}
      <div className="bg-white rounded-xl border-2 border-gray-200 p-8 mb-8">
        <form onSubmit={handleUpload}>
          {/* File Drop Zone */}
          <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 mb-6">
            <input
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              onChange={(e) => setData('file', e.target.files[0])}
              className="hidden"
              id="file-upload"
            />
            <label
              htmlFor="file-upload"
              className="cursor-pointer flex flex-col items-center"
            >
              <Upload size={48} className="text-gray-400 mb-4" />
              <span className="text-lg text-gray-700 font-medium mb-2">
                {data.file ? data.file.name : 'Click to upload or drag and drop'}
              </span>
              <span className="text-sm text-gray-500">
                PDF, JPG, or PNG (max 10MB)
              </span>
            </label>
          </div>

          {/* Historical Data Checkbox */}
          <label className="flex items-center gap-2 mb-6">
            <input
              type="checkbox"
              checked={data.is_historical}
              onChange={(e) => setData('is_historical', e.target.checked)}
              className="rounded"
            />
            <span className="text-sm text-gray-700">
              This is historical data (old documents)
            </span>
          </label>

          {/* Upload Button */}
          <button
            type="submit"
            disabled={!data.file || processing}
            className="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 font-medium"
          >
            {processing ? (
              <>
                <Loader size={20} className="animate-spin" />
                <span>Uploading...</span>
              </>
            ) : (
              <>
                <Upload size={20} />
                <span>Upload & Process</span>
              </>
            )}
          </button>
        </form>

        {/* Upload Status Message */}
        {uploadStatus && (
          <div
            className={`mt-6 p-4 rounded-lg ${
              uploadStatus.type === 'success'
                ? 'bg-green-50 border border-green-200'
                : 'bg-red-50 border border-red-200'
            }`}
          >
            <div className="flex items-center gap-2">
              {uploadStatus.type === 'success' ? (
                <CheckCircle size={20} className="text-green-600" />
              ) : (
                <AlertCircle size={20} className="text-red-600" />
              )}
              <span
                className={
                  uploadStatus.type === 'success' ? 'text-green-800' : 'text-red-800'
                }
              >
                {uploadStatus.message}
              </span>
            </div>
          </div>
        )}
      </div>

      {/* Processing Jobs */}
      {Object.keys(processingJobs).length > 0 && (
        <div className="bg-white rounded-xl border border-gray-200 p-6 mb-8">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">
            Currently Processing
          </h2>
          <div className="space-y-4">
            {Object.values(processingJobs).map((job) => {
              const statusDisplay = getStatusDisplay(job.status);
              const Icon = statusDisplay.icon;

              return (
                <div
                  key={job.job_id}
                  className="flex items-center gap-4 p-4 bg-gray-50 rounded-lg"
                >
                  <div className={`p-2 rounded-lg ${statusDisplay.bgColor}`}>
                    <Icon
                      size={24}
                      className={`${statusDisplay.color} ${
                        statusDisplay.animate ? 'animate-spin' : ''
                      }`}
                    />
                  </div>

                  <div className="flex-1">
                    <div className="font-medium text-gray-900 mb-1">
                      {job.status_message || 'Processing...'}
                    </div>
                    
                    {/* Progress Bar */}
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div
                        className="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                        style={{ width: `${job.progress}%` }}
                      />
                    </div>
                    
                    <div className="flex items-center gap-4 mt-2 text-xs text-gray-500">
                      <span>{job.progress}% complete</span>
                      {job.processing_time && (
                        <span>• {job.processing_time}s</span>
                      )}
                    </div>
                  </div>

                  <div className={`px-3 py-1 rounded-full text-xs font-medium ${statusDisplay.bgColor} ${statusDisplay.color}`}>
                    {statusDisplay.label}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Recent Jobs */}
      {recentJobs.length > 0 && (
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">
            Recent Documents
          </h2>
          <div className="space-y-3">
            {recentJobs.map((job) => {
              const statusDisplay = getStatusDisplay(job.status);
              const Icon = statusDisplay.icon;

              return (
                <div
                  key={job.id}
                  className="flex items-center justify-between p-4 hover:bg-gray-50 rounded-lg transition-colors"
                >
                  <div className="flex items-center gap-3">
                    <div className={`p-2 rounded-lg ${statusDisplay.bgColor}`}>
                      <Icon size={20} className={statusDisplay.color} />
                    </div>
                    <div>
                      <div className="font-medium text-gray-900">
                        {job.file_name}
                      </div>
                      <div className="text-sm text-gray-500">
                        {job.created_at}
                        {job.document_type && (
                          <span className="ml-2">• {job.document_type}</span>
                        )}
                        {job.confidence && (
                          <span className="ml-2">• {Math.round(job.confidence * 100)}% confident</span>
                        )}
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    {job.requires_review && (
                      <button className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                        Review
                      </button>
                    )}
                    <span className={`px-3 py-1 rounded-full text-xs font-medium ${statusDisplay.bgColor} ${statusDisplay.color}`}>
                      {statusDisplay.label}
                    </span>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Info Box */}
      <div className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div className="flex gap-3">
          <AlertCircle size={20} className="text-blue-600 flex-shrink-0 mt-0.5" />
          <div className="text-sm text-blue-800">
            <strong>How it works:</strong> Upload your document and our AI agent will
            process it in the background. You'll see real-time updates as it extracts,
            analyzes, and imports your data. If the agent is confident (85%+), it will
            auto-import. Otherwise, you'll review the extracted data before importing.
          </div>
        </div>
      </div>
    </div>
  );
};

export default AgentDataUpload;

