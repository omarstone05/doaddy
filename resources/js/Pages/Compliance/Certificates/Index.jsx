import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Edit, Trash2, AlertTriangle, Calendar, Award } from 'lucide-react';

export default function CertificatesIndex({ certificates, categories, filters }) {
    const handleDelete = (certificateId) => {
        if (confirm('Are you sure you want to delete this certificate?')) {
            router.delete(`/compliance/certificates/${certificateId}`);
        }
    };

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
            <Head title="Certificates" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Certificates</h1>
                        <p className="text-gray-500 mt-1">Manage your organization's certificates</p>
                    </div>
                    <Button onClick={() => router.visit('/compliance/certificates/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Certificate
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={filters?.search || ''}
                                onChange={(e) => router.visit(`/compliance/certificates?search=${e.target.value}`)}
                                placeholder="Search certificates..."
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.status || ''}
                                onChange={(e) => router.visit(`/compliance/certificates?status=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="pending_renewal">Pending Renewal</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select
                                value={filters?.category || ''}
                                onChange={(e) => router.visit(`/compliance/certificates?category=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Categories</option>
                                {categories.map((category) => (
                                    <option key={category} value={category}>
                                        {category}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Filter</label>
                            <select
                                value={filters?.expiring || ''}
                                onChange={(e) => router.visit(`/compliance/certificates?expiring=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All</option>
                                <option value="true">Expiring Soon</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Certificates Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Certificate Number</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Issuing Authority</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Expiry Date</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {certificates.data.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                                        No certificates found. Create your first certificate to get started.
                                    </td>
                                </tr>
                            ) : (
                                certificates.data.map((certificate) => (
                                    <tr key={certificate.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">{certificate.name}</div>
                                            {certificate.category && (
                                                <div className="text-sm text-gray-500 mt-1">{certificate.category}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {certificate.certificate_number || '-'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {certificate.issuing_authority}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2">
                                                {isExpiringSoon(certificate.expiry_date) && (
                                                    <AlertTriangle className="h-4 w-4 text-yellow-500" />
                                                )}
                                                <span className="text-sm text-gray-900">
                                                    {certificate.expiry_date ? new Date(certificate.expiry_date).toLocaleDateString() : '-'}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusBadge(certificate.status)}`}>
                                                {certificate.status.replace('_', ' ').charAt(0).toUpperCase() + certificate.status.replace('_', ' ').slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={`/compliance/certificates/${certificate.id}/edit`}>
                                                    <button className="text-gray-400 hover:text-teal-600">
                                                        <Edit className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(certificate.id)}
                                                    className="text-gray-400 hover:text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {certificates.links && certificates.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {certificates.from} to {certificates.to} of {certificates.total} results
                        </div>
                        <div className="flex gap-2">
                            {certificates.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.visit(link.url)}
                                    disabled={!link.url}
                                    className={`px-3 py-2 text-sm border rounded-lg ${
                                        link.active
                                            ? 'bg-teal-500 text-white border-teal-500'
                                            : link.url
                                            ? 'border-gray-300 hover:bg-gray-50'
                                            : 'border-gray-200 text-gray-400 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

