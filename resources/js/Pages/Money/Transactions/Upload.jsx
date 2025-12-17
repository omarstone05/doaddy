import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft, Upload, FileText, CheckCircle } from 'lucide-react';

export default function TransactionsUpload({ accounts }) {
    const [selectedFile, setSelectedFile] = useState(null);
    const [fileType, setFileType] = useState('csv');

    const { data, setData, post, processing, errors } = useForm({
        file: null,
        account_id: '',
        file_type: 'csv',
    });

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setSelectedFile(file);
            setData('file', file);
            
            // Detect file type from extension
            const ext = file.name.split('.').pop().toLowerCase();
            if (['ofx'].includes(ext)) {
                setFileType('ofx');
                setData('file_type', 'ofx');
            } else if (['qbo', 'qfx'].includes(ext)) {
                setFileType('qbo');
                setData('file_type', 'qbo');
            } else {
                setFileType('csv');
                setData('file_type', 'csv');
            }
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post('/transactions/upload', {
            forceFormData: true,
            onSuccess: () => {
                router.visit('/transactions');
            },
        });
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title="Upload Transactions" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit('/transactions')}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Upload transactions</h1>
                    <p className="text-gray-500 mt-1">Import transactions from your bank statement</p>
                </div>

                <div className="space-y-6">
                    {/* Step 1: Download Statement */}
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            1. Download your electronic statement
                        </h2>
                        <p className="text-sm text-gray-600 mb-4">
                            Visit your online banking website and download an electronic statement for your bank or credit card account in any of these file types:
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <div className="px-4 py-2 bg-teal-50 border border-teal-200 rounded-full text-sm font-medium text-teal-700">
                                .OFX Microsoft Money
                            </div>
                            <div className="px-4 py-2 bg-teal-50 border border-teal-200 rounded-full text-sm font-medium text-teal-700">
                                .QBO QuickBooks
                            </div>
                            <div className="px-4 py-2 bg-teal-50 border border-teal-200 rounded-full text-sm font-medium text-teal-700">
                                .QFX Quicken
                            </div>
                            <div className="px-4 py-2 bg-teal-50 border border-teal-200 rounded-full text-sm font-medium text-teal-700">
                                .CSV CSV file
                            </div>
                        </div>
                    </Card>

                    {/* Step 2: Upload Statement */}
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            2. Upload your statement
                        </h2>
                        <p className="text-sm text-gray-600 mb-6">
                            Once you have your electronic statement downloaded to your computer, upload it using the form below.
                        </p>

                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <label htmlFor="file" className="block text-sm font-medium text-gray-700 mb-2">
                                    Statement *
                                </label>
                                <div className="flex items-center gap-4">
                                    <label className="flex-1">
                                        <input
                                            type="file"
                                            id="file"
                                            accept=".csv,.ofx,.qbo,.qfx"
                                            onChange={handleFileChange}
                                            className="hidden"
                                        />
                                        <div className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                            <FileText className="h-5 w-5 text-gray-400" />
                                            <span className="text-sm text-gray-700">
                                                {selectedFile ? selectedFile.name : 'Choose file'}
                                            </span>
                                        </div>
                                    </label>
                                    {selectedFile && (
                                        <CheckCircle className="h-5 w-5 text-green-500" />
                                    )}
                                </div>
                                {errors.file && <p className="mt-1 text-sm text-red-600">{errors.file}</p>}
                            </div>

                            <div>
                                <label htmlFor="account_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Payment account *
                                </label>
                                <div className="flex items-center gap-2">
                                    <select
                                        id="account_id"
                                        value={data.account_id}
                                        onChange={(e) => setData('account_id', e.target.value)}
                                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Select account</option>
                                        {accounts.map((account) => (
                                            <option key={account.id} value={account.id}>
                                                {account.name}
                                            </option>
                                        ))}
                                    </select>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => router.visit('/money/accounts/create')}
                                    >
                                        +
                                    </Button>
                                </div>
                                <p className="mt-1 text-xs text-gray-500">
                                    Select the account for which you're uploading a statement, or add a new account.
                                </p>
                                {errors.account_id && <p className="mt-1 text-sm text-red-600">{errors.account_id}</p>}
                            </div>

                            <div className="flex justify-end gap-4 pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.visit('/transactions')}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing || !selectedFile || !data.account_id}>
                                    <Upload className="h-4 w-4 mr-2" />
                                    {processing ? 'Uploading...' : 'Upload'}
                                </Button>
                            </div>
                        </form>
                    </Card>

                    {/* Help Section */}
                    <Card className="p-6 bg-gray-50">
                        <h3 className="text-sm font-semibold text-gray-900 mb-2">
                            Can't find the account you're looking for? Create an account using the instructions below:
                        </h3>
                        <ul className="text-sm text-gray-600 space-y-1 list-disc list-inside">
                            <li>Click "+" beside Payment Account drop-down menu</li>
                            <li>Find savings and checking accounts under Asset &gt; Bank &gt; Bank &amp; Cash; you may create a custom account by selecting Other Bank Account</li>
                            <li>Find credit cards and lines of credit under Liability/Credit Card &gt; Current Liability; you may create a custom account by selecting Other</li>
                        </ul>
                    </Card>
                </div>
            </div>
        </SectionLayout>
    );
}

