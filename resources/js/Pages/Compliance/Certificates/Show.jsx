import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Edit, Award, Calendar, User, Tag, AlertTriangle } from 'lucide-react';

export default function CertificatesShow({ certificate }) {
    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-green-100 text-green-700',
            expired: 'bg-red-100 text-red-700',
            pending_renewal: 'bg-yellow-100 text-yellow-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    const isExpiringSoon = (expiryDate) => {
        if (!expiryDate) return false;
        const expiry = new Date(expiryDate);
        const daysUntilExpiry = Math.ceil((expiry - new Date()) / (1000 * 60 * 60 * 24));
        return daysUntilExpiry <= 30 && daysUntilExpiry > 0;
    };

    return (
        <SectionLayout sectionName="Compliance">
            <Head title={certificate.name} />
            <div className="max-w-4xl mx-auto">
                <Link href="/compliance/certificates">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Certificates
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-3">
                            <Award className="h-8 w-8 text-teal-600" />
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">{certificate.name}</h1>
                                {certificate.certificate_number && (
                                    <p className="text-gray-500 mt-1">Certificate #: {certificate.certificate_number}</p>
                                )}
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            {isExpiringSoon(certificate.expiry_date) && (
                                <div className="flex items-center gap-2 text-yellow-600">
                                    <AlertTriangle className="h-5 w-5" />
                                    <span className="text-sm font-medium">Expiring Soon</span>
                                </div>
                            )}
                            <span className={`px-3 py-1 text-sm font-medium rounded-full ${getStatusBadge(certificate.status)}`}>
                                {certificate.status.charAt(0).toUpperCase() + certificate.status.slice(1).replace('_', ' ')}
                            </span>
                            <Link href={`/compliance/certificates/${certificate.id}/edit`}>
                                <Button variant="secondary">
                                    <Edit className="h-4 w-4 mr-2" />
                                    Edit
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {certificate.description && (
                        <div className="mb-6">
                            <p className="text-gray-700">{certificate.description}</p>
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Tag className="h-4 w-4" />
                                <span className="text-sm font-medium">Category</span>
                            </div>
                            <p className="text-gray-900">{certificate.category || '-'}</p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Award className="h-4 w-4" />
                                <span className="text-sm font-medium">Issuing Authority</span>
                            </div>
                            <p className="text-gray-900">{certificate.issuing_authority || '-'}</p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Calendar className="h-4 w-4" />
                                <span className="text-sm font-medium">Issue Date</span>
                            </div>
                            <p className="text-gray-900">
                                {certificate.issue_date 
                                    ? new Date(certificate.issue_date).toLocaleDateString('en-US', { 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric' 
                                    })
                                    : '-'
                                }
                            </p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Calendar className="h-4 w-4" />
                                <span className="text-sm font-medium">Expiry Date</span>
                            </div>
                            <p className="text-gray-900">
                                {certificate.expiry_date 
                                    ? new Date(certificate.expiry_date).toLocaleDateString('en-US', { 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric' 
                                    })
                                    : 'No expiry date'
                                }
                            </p>
                        </div>

                        {certificate.created_by && (
                            <div>
                                <div className="flex items-center gap-2 text-gray-600 mb-2">
                                    <User className="h-4 w-4" />
                                    <span className="text-sm font-medium">Created By</span>
                                </div>
                                <p className="text-gray-900">{certificate.created_by.name || '-'}</p>
                            </div>
                        )}
                    </div>

                    {certificate.notes && (
                        <div className="border-t border-gray-200 pt-6">
                            <h3 className="text-sm font-medium text-gray-700 mb-2">Notes</h3>
                            <p className="text-gray-900 whitespace-pre-wrap">{certificate.notes}</p>
                        </div>
                    )}
                </div>
            </div>
        </SectionLayout>
    );
}

