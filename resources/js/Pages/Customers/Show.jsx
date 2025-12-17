import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import FileUpload from '@/Components/FileUpload';
import { ArrowLeft, Edit, User, Mail, Phone, Building, MapPin, FileText } from 'lucide-react';

export default function CustomersShow({ customer }) {
    return (
        <SectionLayout sectionName="Sales">
            <Head title={`Customer - ${customer.name}`} />
            <div className="max-w-4xl mx-auto">
                {/* Back Button */}
                <Button
                    variant="ghost"
                    onClick={() => window.history.back()}
                    className="mb-6 gap-2"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back
                </Button>

                {/* Header Card */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 mb-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white text-2xl font-bold">
                                {customer.name?.charAt(0)?.toUpperCase()}
                            </div>
                            <div>
                                <h1 className="text-2xl font-black text-gray-900">{customer.name}</h1>
                                {customer.company_name && (
                                    <p className="text-gray-500 font-medium">{customer.company_name}</p>
                                )}
                            </div>
                        </div>
                        <Link href={`/customers/${customer.id}/edit`}>
                            <Button variant="secondary" className="gap-2">
                                <Edit className="h-4 w-4" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Details Card */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 mb-6">
                    <h2 className="text-lg font-bold text-gray-900 mb-6">Contact Details</h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {customer.email && (
                            <div className="p-4 bg-gray-50 rounded-xl">
                                <div className="flex items-center gap-2 mb-2">
                                    <div className="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center">
                                        <Mail className="h-4 w-4 text-teal-600" />
                                    </div>
                                    <h3 className="text-sm font-semibold text-gray-600">Email</h3>
                                </div>
                                <p className="text-gray-900 font-medium pl-10">{customer.email}</p>
                            </div>
                        )}

                        {customer.phone && (
                            <div className="p-4 bg-gray-50 rounded-xl">
                                <div className="flex items-center gap-2 mb-2">
                                    <div className="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center">
                                        <Phone className="h-4 w-4 text-teal-600" />
                                    </div>
                                    <h3 className="text-sm font-semibold text-gray-600">Phone</h3>
                                </div>
                                <p className="text-gray-900 font-medium pl-10">{customer.phone}</p>
                            </div>
                        )}

                        {customer.company_name && (
                            <div className="p-4 bg-gray-50 rounded-xl">
                                <div className="flex items-center gap-2 mb-2">
                                    <div className="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center">
                                        <Building className="h-4 w-4 text-teal-600" />
                                    </div>
                                    <h3 className="text-sm font-semibold text-gray-600">Company</h3>
                                </div>
                                <p className="text-gray-900 font-medium pl-10">{customer.company_name}</p>
                            </div>
                        )}

                        {customer.tax_id && (
                            <div className="p-4 bg-gray-50 rounded-xl">
                                <div className="flex items-center gap-2 mb-2">
                                    <div className="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center">
                                        <FileText className="h-4 w-4 text-teal-600" />
                                    </div>
                                    <h3 className="text-sm font-semibold text-gray-600">Tax ID</h3>
                                </div>
                                <p className="text-gray-900 font-medium pl-10">{customer.tax_id}</p>
                            </div>
                        )}

                        {customer.address && (
                            <div className="p-4 bg-gray-50 rounded-xl md:col-span-2">
                                <div className="flex items-center gap-2 mb-2">
                                    <div className="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center">
                                        <MapPin className="h-4 w-4 text-teal-600" />
                                    </div>
                                    <h3 className="text-sm font-semibold text-gray-600">Address</h3>
                                </div>
                                <p className="text-gray-900 font-medium pl-10 whitespace-pre-line">{customer.address}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Attachments Card */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50">
                    <h2 className="text-lg font-bold text-gray-900 mb-6">Attachments</h2>
                    <FileUpload
                        attachableType="App\Models\Customer"
                        attachableId={customer.id}
                        category="customer"
                        existingAttachments={customer.attachments || []}
                    />
                </div>
            </div>
        </SectionLayout>
    );
}
