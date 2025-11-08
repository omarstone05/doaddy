import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, FileText, Calendar, User, Tag } from 'lucide-react';

export default function DocumentsShow({ document }) {
    const getStatusBadge = (status) => {
        const badges = {
            draft: 'bg-gray-100 text-gray-700',
            active: 'bg-green-100 text-green-700',
            archived: 'bg-yellow-100 text-yellow-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    return (
        <SectionLayout sectionName="Compliance">
            <Head title={document.name} />
            <div className="max-w-4xl mx-auto">
                <Link href="/compliance/documents">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Documents
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-3">
                            <FileText className="h-8 w-8 text-teal-600" />
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">{document.name}</h1>
                                {document.description && (
                                    <p className="text-gray-500 mt-1">{document.description}</p>
                                )}
                            </div>
                        </div>
                        <span className={`px-3 py-1 text-sm font-medium rounded-full ${getStatusBadge(document.status)}`}>
                            {document.status.charAt(0).toUpperCase() + document.status.slice(1)}
                        </span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Tag className="h-4 w-4" />
                                <span className="text-sm font-medium">Category</span>
                            </div>
                            <p className="text-gray-900">{document.category || '-'}</p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <span className="text-sm font-medium">Type</span>
                            </div>
                            <p className="text-gray-900">{document.type || '-'}</p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <User className="h-4 w-4" />
                                <span className="text-sm font-medium">Created By</span>
                            </div>
                            <p className="text-gray-900">{document.created_by?.name || 'N/A'}</p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Calendar className="h-4 w-4" />
                                <span className="text-sm font-medium">Created At</span>
                            </div>
                            <p className="text-gray-900">{new Date(document.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>

                    {document.versions && document.versions.length > 0 && (
                        <div className="border-t border-gray-200 pt-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Versions</h2>
                            <div className="space-y-2">
                                {document.versions.map((version) => (
                                    <div key={version.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <div className="font-medium text-gray-900">Version {version.version_number}</div>
                                            {version.changes && (
                                                <div className="text-sm text-gray-500 mt-1">{version.changes}</div>
                                            )}
                                        </div>
                                        <div className="text-sm text-gray-500">
                                            {new Date(version.created_at).toLocaleDateString()}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                <div className="flex gap-3">
                    <Link href={`/compliance/documents/${document.id}/edit`}>
                        <Button>Edit Document</Button>
                    </Link>
                </div>
            </div>
        </SectionLayout>
    );
}

