import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Edit, UserPlus, Mail, Phone, Building2 } from 'lucide-react';

export default function ProspectsShow({ prospect }) {
    if (!prospect) {
        return (
            <SectionLayout sectionName="Sales">
                <Head title="Prospect Not Found" />
                <div className="text-center py-12">
                    <h1 className="text-2xl font-bold text-gray-900 mb-4">Prospect not found</h1>
                    <Button variant="secondary" onClick={() => window.history.back()}>
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Go Back
                    </Button>
                </div>
            </SectionLayout>
        );
    }

    const getStageColor = (stage) => {
        const colors = {
            lead: 'bg-gray-100 text-gray-700',
            contacted: 'bg-blue-100 text-blue-700',
            qualified: 'bg-yellow-100 text-yellow-700',
            proposal: 'bg-purple-100 text-purple-700',
            negotiation: 'bg-orange-100 text-orange-700',
            won: 'bg-green-100 text-green-700',
            lost: 'bg-red-100 text-red-700',
        };
        return colors[stage] || colors.lead;
    };

    const handleConvertToCustomer = () => {
        if (confirm('Convert this prospect to a customer? This will create a new customer record.')) {
            router.post(`/prospects/${prospect.id}/convert`);
        }
    };

    return (
        <SectionLayout sectionName="Sales">
            <Head title={`Prospect - ${prospect.name || prospect.company_name}`} />
            <div className="max-w-3xl mx-auto">
                <Button
                    variant="ghost"
                    onClick={() => window.history.back()}
                    className="mb-4"
                >
                    <ArrowLeft className="h-4 w-4 mr-2" />
                    Back
                </Button>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-start justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                {prospect.company_name || prospect.name}
                            </h1>
                            {prospect.company_name && prospect.name && (
                                <p className="text-gray-600 mt-1">{prospect.name}</p>
                            )}
                            <span className={`inline-block mt-2 px-4 py-1 rounded-full text-sm font-medium ${getStageColor(prospect.stage)}`}>
                                {prospect.stage}
                            </span>
                        </div>
                        <div className="flex gap-2">
                            <Link href={`/prospects/${prospect.id}/edit`}>
                                <Button variant="secondary" size="sm">
                                    <Edit className="h-4 w-4 mr-2" />
                                    Edit
                                </Button>
                            </Link>
                            {!prospect.converted_to_customer_id && (
                                <Button onClick={handleConvertToCustomer}>
                                    <UserPlus className="h-4 w-4 mr-2" />
                                    Convert to Customer
                                </Button>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
                        {prospect.email && (
                            <div className="flex items-center gap-3">
                                <Mail className="h-5 w-5 text-gray-400" />
                                <div>
                                    <p className="text-xs text-gray-500 uppercase">Email</p>
                                    <a href={`mailto:${prospect.email}`} className="text-teal-600 hover:underline">
                                        {prospect.email}
                                    </a>
                                </div>
                            </div>
                        )}
                        {prospect.phone && (
                            <div className="flex items-center gap-3">
                                <Phone className="h-5 w-5 text-gray-400" />
                                <div>
                                    <p className="text-xs text-gray-500 uppercase">Phone</p>
                                    <p className="text-gray-900">{prospect.phone}</p>
                                </div>
                            </div>
                        )}
                        {prospect.estimated_value > 0 && (
                            <div className="flex items-center gap-3">
                                <Building2 className="h-5 w-5 text-gray-400" />
                                <div>
                                    <p className="text-xs text-gray-500 uppercase">Estimated Value</p>
                                    <p className="text-gray-900">
                                        {new Intl.NumberFormat('en-ZM', {
                                            style: 'currency',
                                            currency: prospect.currency || 'ZMW',
                                        }).format(prospect.estimated_value)}
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>

                    {prospect.converted_to_customer_id && (
                        <div className="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p className="text-sm text-green-800">
                                This prospect has been converted to a customer.
                            </p>
                            <Link
                                href={`/customers/${prospect.converted_to_customer_id}`}
                                className="inline-flex items-center mt-2 text-sm font-medium text-teal-600 hover:text-teal-700"
                            >
                                View Customer →
                            </Link>
                        </div>
                    )}

                    {prospect.notes && (
                        <div className="mt-6 pt-6 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-700 mb-2">Notes</h3>
                            <p className="text-gray-600">{prospect.notes}</p>
                        </div>
                    )}
                </div>
            </div>
        </SectionLayout>
    );
}
