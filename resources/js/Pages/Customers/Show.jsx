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
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{customer.name}</h1>
                            {customer.company_name && (
                                <p className="text-gray-500 mt-1">{customer.company_name}</p>
                            )}
                        </div>
                        <Link href={`/customers/${customer.id}/edit`}>
                            <Button variant="secondary">
                                <Edit className="h-4 w-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="grid grid-cols-2 gap-6">
                        {customer.email && (
                            <div>
                                <div className="flex items-center gap-2 mb-2">
                                    <Mail className="h-4 w-4 text-gray-400" />
                                    <h3 className="text-sm font-medium text-gray-500">Email</h3>
                                </div>
                                <p className="text-gray-900">{customer.email}</p>
                            </div>
                        )}

                        {customer.phone && (
                            <div>
                                <div className="flex items-center gap-2 mb-2">
                                    <Phone className="h-4 w-4 text-gray-400" />
                                    <h3 className="text-sm font-medium text-gray-500">Phone</h3>
                                </div>
                                <p className="text-gray-900">{customer.phone}</p>
                            </div>
                        )}

                        {customer.company_name && (
                            <div>
                                <div className="flex items-center gap-2 mb-2">
                                    <Building className="h-4 w-4 text-gray-400" />
                                    <h3 className="text-sm font-medium text-gray-500">Company</h3>
                                </div>
                                <p className="text-gray-900">{customer.company_name}</p>
                            </div>
                        )}

                        {customer.tax_id && (
                            <div>
                                <div className="flex items-center gap-2 mb-2">
                                    <FileText className="h-4 w-4 text-gray-400" />
                                    <h3 className="text-sm font-medium text-gray-500">Tax ID</h3>
                                </div>
                                <p className="text-gray-900">{customer.tax_id}</p>
                            </div>
                        )}

                        {customer.address && (
                            <div className="col-span-2">
                                <div className="flex items-center gap-2 mb-2">
                                    <MapPin className="h-4 w-4 text-gray-400" />
                                    <h3 className="text-sm font-medium text-gray-500">Address</h3>
                                </div>
                                <p className="text-gray-900 whitespace-pre-line">{customer.address}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Attachments */}
                <div className="bg-white border border-gray-200 rounded-lg p-6">
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

