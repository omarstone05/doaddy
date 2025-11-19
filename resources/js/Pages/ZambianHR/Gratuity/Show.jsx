import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Calculator } from 'lucide-react';

export default function ShowGratuity({ calculation }) {
    if (!calculation) {
        return (
            <SectionLayout sectionName="Zambian HR">
                <Head title="Gratuity Calculation Not Found" />
                <div className="max-w-4xl mx-auto">
                    <Card className="p-6 text-center">
                        <p className="text-gray-500">Gratuity calculation not found</p>
                        <Link href="/zambian-hr/gratuity" className="mt-4 inline-block">
                            <Button>Back to Gratuity</Button>
                        </Link>
                    </Card>
                </div>
            </SectionLayout>
        );
    }

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Gratuity Calculation" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/gratuity" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Gratuity
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Gratuity Calculation</h1>
                </div>

                <Card className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Years of Service</h3>
                            <p className="text-gray-900">{calculation.years_of_service || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Base Salary Used</h3>
                            <p className="text-gray-900">
                                {calculation.base_salary_used ? `ZMW ${parseFloat(calculation.base_salary_used).toLocaleString()}` : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Gratuity Rate</h3>
                            <p className="text-gray-900">{calculation.gratuity_rate ? `${(calculation.gratuity_rate * 100).toFixed(0)}%` : '25%'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Total Gratuity Amount</h3>
                            <p className="text-2xl font-bold text-teal-600">
                                {calculation.total_gratuity_amount ? `ZMW ${parseFloat(calculation.total_gratuity_amount).toLocaleString()}` : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Status</h3>
                            <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                calculation.status === 'paid' ? 'bg-green-100 text-green-700' :
                                calculation.status === 'approved' ? 'bg-blue-100 text-blue-700' :
                                'bg-gray-100 text-gray-700'
                            }`}>
                                {calculation.status ? calculation.status.charAt(0).toUpperCase() + calculation.status.slice(1) : 'N/A'}
                            </span>
                        </div>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

