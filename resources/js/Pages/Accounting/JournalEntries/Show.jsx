import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft, CheckCircle, XCircle, RotateCcw } from 'lucide-react';
import { useState } from 'react';

export default function JournalEntriesShow({ journalEntry }) {
    const [isPosting, setIsPosting] = useState(false);
    const [isReversing, setIsReversing] = useState(false);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    const getStatusBadge = (status) => {
        const styles = {
            draft: 'bg-gray-100 text-gray-700',
            posted: 'bg-green-100 text-green-700',
            reversed: 'bg-red-100 text-red-700',
        };
        return (
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ${styles[status] || styles.draft}`}>
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    const handlePost = () => {
        if (confirm('Are you sure you want to post this journal entry? This action cannot be undone.')) {
            setIsPosting(true);
            router.post(`/accounting/journal-entries/${journalEntry.id}/post`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setIsPosting(false),
            });
        }
    };

    const handleReverse = () => {
        const reason = prompt('Enter reason for reversal (optional):');
        if (reason !== null) {
            setIsReversing(true);
            router.post(`/accounting/journal-entries/${journalEntry.id}/reverse`, {
                reason: reason || null,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setIsReversing(false),
            });
        }
    };

    const totalDebits = journalEntry.lines
        ?.filter(line => line.type === 'debit')
        .reduce((sum, line) => sum + parseFloat(line.amount || 0), 0) || 0;
    
    const totalCredits = journalEntry.lines
        ?.filter(line => line.type === 'credit')
        .reduce((sum, line) => sum + parseFloat(line.amount || 0), 0) || 0;

    return (
        <SectionLayout sectionName="Accounting">
            <Head title={`Journal Entry - ${journalEntry.entry_number}`} />
            <div className="max-w-6xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit('/accounting/journal-entries')}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{journalEntry.entry_number}</h1>
                            <p className="text-gray-500 mt-1">{journalEntry.description}</p>
                        </div>
                        <div className="flex items-center gap-2">
                            {getStatusBadge(journalEntry.status)}
                            {journalEntry.status === 'draft' && (
                                <Button onClick={handlePost} disabled={isPosting} className="gap-2">
                                    <CheckCircle className="h-4 w-4" />
                                    {isPosting ? 'Posting...' : 'Post Entry'}
                                </Button>
                            )}
                            {journalEntry.status === 'posted' && !journalEntry.reversing_entry && (
                                <Button onClick={handleReverse} disabled={isReversing} variant="outline" className="gap-2">
                                    <RotateCcw className="h-4 w-4" />
                                    {isReversing ? 'Reversing...' : 'Reverse Entry'}
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <Card className="p-6">
                        <p className="text-sm font-semibold text-gray-600 mb-2">Entry Date</p>
                        <p className="text-lg font-bold text-gray-900">
                            {new Date(journalEntry.entry_date).toLocaleDateString()}
                        </p>
                    </Card>
                    <Card className="p-6">
                        <p className="text-sm font-semibold text-gray-600 mb-2">Total Debits</p>
                        <p className="text-lg font-bold text-gray-900">
                            {formatCurrency(totalDebits)}
                        </p>
                    </Card>
                    <Card className="p-6">
                        <p className="text-sm font-semibold text-gray-600 mb-2">Total Credits</p>
                        <p className="text-lg font-bold text-gray-900">
                            {formatCurrency(totalCredits)}
                        </p>
                    </Card>
                </div>

                {journalEntry.reference && (
                    <Card className="p-6 mb-6">
                        <p className="text-sm font-semibold text-gray-600 mb-1">Reference</p>
                        <p className="text-sm text-gray-900">{journalEntry.reference}</p>
                    </Card>
                )}

                {journalEntry.posted_at && (
                    <Card className="p-6 mb-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm font-semibold text-gray-600 mb-1">Posted At</p>
                                <p className="text-sm text-gray-900">
                                    {new Date(journalEntry.posted_at).toLocaleString()}
                                </p>
                            </div>
                            {journalEntry.posted_by && (
                                <div>
                                    <p className="text-sm font-semibold text-gray-600 mb-1">Posted By</p>
                                    <p className="text-sm text-gray-900">
                                        {journalEntry.posted_by?.name || 'System'}
                                    </p>
                                </div>
                            )}
                        </div>
                    </Card>
                )}

                <Card className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Journal Entry Lines</h2>
                    {journalEntry.lines && journalEntry.lines.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Account</th>
                                        <th className="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Type</th>
                                        <th className="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Amount</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Description</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Reference</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {journalEntry.lines.map((line) => (
                                        <tr key={line.id} className="hover:bg-teal-50/30 transition-colors">
                                            <td className="px-4 py-3">
                                                <div>
                                                    <p className="text-sm font-mono text-gray-900">{line.account?.code}</p>
                                                    <p className="text-xs text-gray-600">{line.account?.name}</p>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                                                    line.type === 'debit' 
                                                        ? 'bg-blue-100 text-blue-700' 
                                                        : 'bg-green-100 text-green-700'
                                                }`}>
                                                    {line.type}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                                {formatCurrency(line.amount)}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {line.description || '-'}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {line.reference || '-'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot className="bg-gray-50 border-t-2 border-gray-200">
                                    <tr>
                                        <td colSpan="2" className="px-4 py-3 text-right font-bold text-gray-900">
                                            Totals:
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="space-y-1">
                                                <p className="text-sm font-bold text-blue-700">
                                                    Debit: {formatCurrency(totalDebits)}
                                                </p>
                                                <p className="text-sm font-bold text-green-700">
                                                    Credit: {formatCurrency(totalCredits)}
                                                </p>
                                            </div>
                                        </td>
                                        <td colSpan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-8">No lines found for this entry</p>
                    )}
                </Card>

                {journalEntry.reversing_entry && (
                    <Card className="p-6 mt-6 bg-yellow-50 border-yellow-200">
                        <div className="flex items-center gap-2 mb-2">
                            <RotateCcw className="h-5 w-5 text-yellow-600" />
                            <h3 className="text-sm font-semibold text-yellow-900">This entry has been reversed</h3>
                        </div>
                        <p className="text-sm text-yellow-800">
                            Reversing entry: {journalEntry.reversing_entry.entry_number}
                        </p>
                    </Card>
                )}
            </div>
        </SectionLayout>
    );
}

