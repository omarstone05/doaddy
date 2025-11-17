import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import OcrReviewInterface from '@/Components/OcrReviewInterface';
import { Upload, FileText, CheckCircle, AlertCircle, Loader2, History } from 'lucide-react';
import axios from 'axios';

export default function Enhanced({ templates }) {
  const [selectedFile, setSelectedFile] = useState(null);
  const [isHistorical, setIsHistorical] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [analysisResult, setAnalysisResult] = useState(null);
  const [needsReview, setNeedsReview] = useState(false);
  const [importing, setImporting] = useState(false);

  const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
      setSelectedFile(file);
      setAnalysisResult(null);
      setNeedsReview(false);
    }
  };

  const handleAnalyze = async () => {
    if (!selectedFile) return;

    setUploading(true);
    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('is_historical', isHistorical);

    try {
      const response = await axios.post('/data-upload/analyze', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      if (response.data.success) {
        const analysis = response.data.analysis;
        setAnalysisResult(analysis);
        setNeedsReview(analysis.requires_review || false);
      } else {
        alert('Analysis failed: ' + (response.data.error || 'Unknown error'));
      }
    } catch (error) {
      console.error('Analysis error:', error);
      alert('Failed to analyze file: ' + (error.response?.data?.message || error.message));
    } finally {
      setUploading(false);
    }
  };

  const handleReviewSubmit = async (reviewedData) => {
    if (!analysisResult) return;

    setImporting(true);
    try {
      const response = await axios.post('/data-upload/import-ocr-reviewed', {
        file_path: analysisResult.file_path,
        document_type: analysisResult.document_type,
        data: reviewedData,
        reviewed: true,
      });

      if (response.data.success) {
        alert('Document imported successfully!');
        // Reset form
        setSelectedFile(null);
        setAnalysisResult(null);
        setNeedsReview(false);
        setIsHistorical(false);
        // Reload page to show new data
        router.reload();
      } else {
        alert('Import failed: ' + (response.data.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Import error:', error);
      alert('Failed to import: ' + (error.response?.data?.message || error.message));
    } finally {
      setImporting(false);
    }
  };

  const handleAutoImport = async () => {
    if (!analysisResult || !analysisResult.auto_importable) return;

    setImporting(true);
    try {
      const response = await axios.post('/data-upload/import-ocr-reviewed', {
        file_path: analysisResult.file_path,
        document_type: analysisResult.document_type,
        data: analysisResult.data,
        reviewed: false,
      });

      if (response.data.success) {
        alert('Document imported successfully!');
        setSelectedFile(null);
        setAnalysisResult(null);
        setNeedsReview(false);
        router.reload();
      } else {
        alert('Import failed: ' + (response.data.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Import error:', error);
      alert('Failed to import: ' + (error.response?.data?.message || error.message));
    } finally {
      setImporting(false);
    }
  };

  const handleCancel = () => {
    setAnalysisResult(null);
    setNeedsReview(false);
    setSelectedFile(null);
  };

  return (
    <AuthenticatedLayout>
      <Head title="Data Upload" />
      <div className="px-6 py-8 max-w-6xl mx-auto">
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900">Upload Documents</h1>
          <p className="mt-1 text-sm text-gray-500">
            Upload receipts, invoices, and other documents to automatically extract and import data
          </p>
        </div>

        {needsReview && analysisResult ? (
          <OcrReviewInterface
            ocrResult={analysisResult}
            onSubmit={handleReviewSubmit}
            onCancel={handleCancel}
          />
        ) : (
          <div className="space-y-6">
            {/* Upload Section */}
            <Card className="p-6">
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Select Document
                  </label>
                  <div className="flex items-center gap-4">
                    <label className="flex-1 cursor-pointer">
                      <input
                        type="file"
                        onChange={handleFileSelect}
                        accept=".pdf,.jpg,.jpeg,.png"
                        className="hidden"
                      />
                      <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-teal-500 transition-colors">
                        {selectedFile ? (
                          <div className="flex items-center justify-center gap-2">
                            <FileText className="w-8 h-8 text-teal-600" />
                            <span className="text-sm font-medium text-gray-900">
                              {selectedFile.name}
                            </span>
                          </div>
                        ) : (
                          <div>
                            <Upload className="w-12 h-12 text-gray-400 mx-auto mb-2" />
                            <p className="text-sm text-gray-600">
                              Click to select or drag and drop
                            </p>
                            <p className="text-xs text-gray-500 mt-1">
                              PDF, JPG, PNG (max 10MB)
                            </p>
                          </div>
                        )}
                      </div>
                    </label>
                  </div>
                </div>

                {/* Historical Data Toggle */}
                <div className="flex items-center gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                  <History className="w-5 h-5 text-amber-600" />
                  <div className="flex-1">
                    <label className="flex items-center gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={isHistorical}
                        onChange={(e) => setIsHistorical(e.target.checked)}
                        className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                      />
                      <span className="text-sm font-medium text-gray-900">
                        This is historical data (from past months/years)
                      </span>
                    </label>
                    <p className="text-xs text-gray-600 mt-1">
                      Check this if you're uploading old receipts or documents to catch up on past records
                    </p>
                  </div>
                </div>

                <Button
                  onClick={handleAnalyze}
                  disabled={!selectedFile || uploading}
                  className="w-full"
                >
                  {uploading ? (
                    <>
                      <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                      Analyzing...
                    </>
                  ) : (
                    <>
                      <Upload className="w-4 h-4 mr-2" />
                      Analyze Document
                    </>
                  )}
                </Button>
              </div>
            </Card>

            {/* Analysis Results */}
            {analysisResult && !needsReview && (
              <Card className="p-6">
                <div className="space-y-4">
                  <div className="flex items-center gap-2">
                    <CheckCircle className="w-6 h-6 text-green-600" />
                    <h3 className="text-lg font-semibold text-gray-900">
                      Analysis Complete
                    </h3>
                  </div>

                  <div className="bg-gray-50 rounded-lg p-4">
                    <h4 className="text-sm font-medium text-gray-700 mb-2">
                      Extracted Data:
                    </h4>
                    <div className="grid grid-cols-2 gap-2 text-sm">
                      {Object.entries(analysisResult.data)
                        .filter(([key, value]) => value && !['raw_text', 'items'].includes(key))
                        .slice(0, 8)
                        .map(([key, value]) => (
                          <div key={key}>
                            <span className="text-gray-500 capitalize">
                              {key.replace(/_/g, ' ')}:
                            </span>{' '}
                            <span className="font-medium text-gray-900">
                              {typeof value === 'object' ? JSON.stringify(value) : String(value)}
                            </span>
                          </div>
                        ))}
                    </div>
                  </div>

                  {analysisResult.auto_importable ? (
                    <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                      <p className="text-sm text-green-800 mb-3">
                        ✓ High confidence - ready to import automatically
                      </p>
                      <Button
                        onClick={handleAutoImport}
                        disabled={importing}
                        className="w-full"
                      >
                        {importing ? (
                          <>
                            <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                            Importing...
                          </>
                        ) : (
                          'Import Now'
                        )}
                      </Button>
                    </div>
                  ) : (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                      <p className="text-sm text-yellow-800">
                        ⚠️ Some fields need review before importing
                      </p>
                    </div>
                  )}
                </div>
              </Card>
            )}
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
}

