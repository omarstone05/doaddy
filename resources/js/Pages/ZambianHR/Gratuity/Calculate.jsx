import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Calculator } from 'lucide-react';

export default function CalculateGratuity({ employee }) {
    const { data, setData, post, processing, errors } = useForm({
        employment_start_date: '',
        employment_end_date: '',
        base_salary: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(`/zambian-hr/gratuity/${employee?.id}/calculate`);
    };

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="Calculate Gratuity" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/gratuity" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Gratuity
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Calculate Gratuity</h1>
                    <p className="text-gray-500 mt-1">Calculate end-of-service gratuity (25% of basic pay per year)</p>
                </div>

                <Card className="p-6">
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Employment Start Date *
                                </label>
                                <input
                                    type="date"
                                    value={data.employment_start_date}
                                    onChange={(e) => setData('employment_start_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.employment_start_date && <p className="text-red-500 text-sm mt-1">{errors.employment_start_date}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Employment End Date *
                                </label>
                                <input
                                    type="date"
                                    value={data.employment_end_date}
                                    onChange={(e) => setData('employment_end_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.employment_end_date && <p className="text-red-500 text-sm mt-1">{errors.employment_end_date}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Base Salary (ZMW) *
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    value={data.base_salary}
                                    onChange={(e) => setData('base_salary', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.base_salary && <p className="text-red-500 text-sm mt-1">{errors.base_salary}</p>}
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-4">
                            <Link href="/zambian-hr/gratuity">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                <Calculator className="h-4 w-4 mr-2" />
                                {processing ? 'Calculating...' : 'Calculate Gratuity'}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </SectionLayout>
    );
}

