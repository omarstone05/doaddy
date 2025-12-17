import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft, Upload, X, File } from 'lucide-react';

export default function IncomeEdit({ income, accounts }) {
    const [uploadedFiles, setUploadedFiles] = useState([]);

    const { data, setData, post, processing, errors } = useForm({
        amount: income.amount || '',
        currency: income.currency || 'ZMW',
        transaction_date: income.transaction_date || new Date().toISOString().split('T')[0],
        to_account_id: income.to_account_id || '',
        description: income.description || '',
        category: income.category || '',
        attachments: [],
    });

    const handleFileChange = (e) => {
        const files = Array.from(e.target.files);
        setUploadedFiles(prev => [...prev, ...files]);
        setData('attachments', [...data.attachments, ...files]);
    };

    const removeFile = (index) => {
        const newFiles = uploadedFiles.filter((_, i) => i !== index);
        setUploadedFiles(newFiles);
        setData('attachments', newFiles);
    };

    const submit = (e) => {
        e.preventDefault();
        post(`/income/${income.id}`, {
            method: 'put',
            forceFormData: true,
            onSuccess: () => {
                router.visit(`/income/${income.id}`);
            },
        });
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title={`Edit Income - ${income.description}`} />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit(`/income/${income.id}`)}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Edit Income</h1>
                    <p className="text-gray-500 mt-1">{income.description}</p>
                </div>

                <form onSubmit={submit}>
                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Income Details</h2>
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="amount" className="block text-sm font-medium text-gray-700 mb-2">
                                        Amount *
                                    </label>
                                    <input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    />
                                    {errors.amount && <p className="mt-1 text-sm text-red-600">{errors.amount}</p>}
                                </div>
                                <div>
                                    <label htmlFor="currency" className="block text-sm font-medium text-gray-700 mb-2">
                                        Currency
                                    </label>
                                    <select
                                        id="currency"
                                        value={data.currency}
                                        onChange={(e) => setData('currency', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    >
                                        <option value="ZMW">ZMW</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="transaction_date" className="block text-sm font-medium text-gray-700 mb-2">
                                        Transaction Date *
                                    </label>
                                    <input
                                        id="transaction_date"
                                        type="date"
                                        value={data.transaction_date}
                                        onChange={(e) => setData('transaction_date', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                <div>
                                    <label htmlFor="to_account_id" className="block text-sm font-medium text-gray-700 mb-2">
                                        To Account *
                                    </label>
                                    <select
                                        id="to_account_id"
                                        value={data.to_account_id}
                                        onChange={(e) => setData('to_account_id', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Select Account</option>
                                        {accounts.map((account) => (
                                            <option key={account.id} value={account.id}>
                                                {account.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                    Description *
                                </label>
                                <input
                                    id="description"
                                    type="text"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                            </div>
                            <div>
                                <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-2">
                                    Category
                                </label>
                                <input
                                    id="category"
                                    type="text"
                                    value={data.category}
                                    onChange={(e) => setData('category', e.target.value)}
                                    placeholder="e.g., Sales, Services, Investment"
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Add More Attachments</h2>
                        <div className="space-y-4">
                            <div>
                                <label htmlFor="attachments" className="block text-sm font-medium text-gray-700 mb-2">
                                    Upload Receipts or Documents
                                </label>
                                <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-teal-400 transition-colors">
                                    <div className="space-y-1 text-center">
                                        <Upload className="mx-auto h-12 w-12 text-gray-400" />
                                        <div className="flex text-sm text-gray-600">
                                            <label htmlFor="attachments" className="relative cursor-pointer bg-white rounded-md font-medium text-teal-600 hover:text-teal-500">
                                                <span>Upload files</span>
                                                <input
                                                    id="attachments"
                                                    name="attachments"
                                                    type="file"
                                                    multiple
                                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                                    onChange={handleFileChange}
                                                    className="sr-only"
                                                />
                                            </label>
                                            <p className="pl-1">or drag and drop</p>
                                        </div>
                                        <p className="text-xs text-gray-500">PDF, JPG, PNG, DOC, DOCX, XLS, XLSX up to 10MB</p>
                                    </div>
                                </div>
                            </div>

                            {uploadedFiles.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-sm font-medium text-gray-700">New Files to Upload:</p>
                                    {uploadedFiles.map((file, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex items-center gap-2">
                                                <File className="h-4 w-4 text-gray-400" />
                                                <span className="text-sm text-gray-900">{file.name}</span>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => removeFile(index)}
                                                className="text-red-600 hover:text-red-800"
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {income.attachments && income.attachments.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-sm font-medium text-gray-700">Existing Attachments:</p>
                                    {income.attachments.map((attachment) => (
                                        <div key={attachment.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex items-center gap-2">
                                                <File className="h-4 w-4 text-gray-400" />
                                                <span className="text-sm text-gray-900">{attachment.name}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </Card>

                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit(`/income/${income.id}`)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Updating...' : 'Update Income'}
                        </Button>
                    </div>
                </form>
            </div>
        </SectionLayout>
    );
}

