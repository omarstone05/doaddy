import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Edit, Phone, Mail, MessageSquare, MapPin, Globe } from 'lucide-react';

export default function LeadsShow({ lead }) {
    const getStatusColor = (status) => {
        const colors = {
            new: 'bg-blue-100 text-blue-800',
            contacted: 'bg-yellow-100 text-yellow-800',
            qualified: 'bg-green-100 text-green-800',
            unqualified: 'bg-gray-100 text-gray-800',
            converted: 'bg-teal-100 text-teal-800',
            lost: 'bg-red-100 text-red-800',
            nurturing: 'bg-purple-100 text-purple-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <SectionLayout sectionName="CRM">
            <Head title={`Lead: ${lead.first_name} ${lead.last_name}`} />
            <div>
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit('/crm/leads')}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Leads
                    </Button>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                {lead.first_name} {lead.last_name}
                            </h1>
                            <p className="text-gray-500 mt-1">
                                {lead.company_name || 'Individual Lead'}
                            </p>
                        </div>
                        <Link href={`/crm/leads/${lead.id}/edit`}>
                            <Button variant="secondary">
                                <Edit className="h-4 w-4 mr-2" />
                                Edit Lead
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Info */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Contact Information */}
                        <div className="bg-white border border-gray-200 rounded-lg p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>
                            <div className="space-y-4">
                                {lead.email && (
                                    <div className="flex items-center gap-3">
                                        <Mail className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-500">Email</p>
                                            <a href={`mailto:${lead.email}`} className="text-teal-600 hover:text-teal-700">
                                                {lead.email}
                                            </a>
                                        </div>
                                    </div>
                                )}
                                {lead.phone && (
                                    <div className="flex items-center gap-3">
                                        <Phone className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-500">Phone</p>
                                            <a href={`tel:${lead.phone}`} className="text-teal-600 hover:text-teal-700">
                                                {lead.phone}
                                            </a>
                                        </div>
                                    </div>
                                )}
                                {lead.whatsapp_number && (
                                    <div className="flex items-center gap-3">
                                        <MessageSquare className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-500">WhatsApp</p>
                                            <a 
                                                href={`https://wa.me/${lead.whatsapp_number.replace(/\D/g, '')}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-teal-600 hover:text-teal-700"
                                            >
                                                {lead.whatsapp_number}
                                            </a>
                                        </div>
                                    </div>
                                )}
                                {lead.address && (
                                    <div className="flex items-center gap-3">
                                        <MapPin className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-500">Address</p>
                                            <p className="text-gray-900">{lead.address}</p>
                                        </div>
                                    </div>
                                )}
                                {lead.website && (
                                    <div className="flex items-center gap-3">
                                        <Globe className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-500">Website</p>
                                            <a 
                                                href={lead.website}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-teal-600 hover:text-teal-700"
                                            >
                                                {lead.website}
                                            </a>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Notes */}
                        {lead.notes && (
                            <div className="bg-white border border-gray-200 rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Notes</h2>
                                <p className="text-gray-700 whitespace-pre-wrap">{lead.notes}</p>
                            </div>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Status Card */}
                        <div className="bg-white border border-gray-200 rounded-lg p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Lead Details</h2>
                            <div className="space-y-4">
                                <div>
                                    <p className="text-sm text-gray-500">Status</p>
                                    <span className={`mt-1 inline-block px-3 py-1 text-sm font-medium rounded-full ${getStatusColor(lead.lead_status)}`}>
                                        {lead.lead_status}
                                    </span>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Source</p>
                                    <p className="mt-1 text-gray-900 capitalize">{lead.lead_source?.replace('_', ' ')}</p>
                                </div>
                                {lead.rating && (
                                    <div>
                                        <p className="text-sm text-gray-500">Rating</p>
                                        <p className="mt-1 text-gray-900 capitalize">{lead.rating}</p>
                                    </div>
                                )}
                                <div>
                                    <p className="text-sm text-gray-500">Lead Score</p>
                                    <p className="mt-1 text-2xl font-bold text-gray-900">{lead.lead_score || 0}</p>
                                </div>
                                {lead.assigned_to && lead.assigned_to_user && (
                                    <div>
                                        <p className="text-sm text-gray-500">Assigned To</p>
                                        <p className="mt-1 text-gray-900">{lead.assigned_to_user.name}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}


