import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function CreateGrievance() {
    const { data, setData, post, processing, errors } = useForm({
        subject: '',
        description: '',
        grievance_category: '',
        incident_date: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/zambian-hr/grievances');
    };

    return (
        <SectionLayout sectionName="Zambian HR">
            <Head title="File Grievance" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/zambian-hr/grievances" className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Grievances
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">File Grievance</h1>
                    <p className="text-gray-500 mt-1">Submit a formal grievance or complaint</p>
                </div>

                <Card className="p-6">
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Subject *
                                </label>
                                <input
                                    type="text"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.subject && <p className="text-red-500 text-sm mt-1">{errors.subject}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Category *
                                </label>
                                <select
                                    value={data.grievance_category}
                                    onChange={(e) => setData('grievance_category', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                >
                                    <option value="">Select...</option>
                                    <option value="harassment">Harassment</option>
                                    <option value="discrimination">Discrimination</option>
                                    <option value="working_conditions">Working Conditions</option>
                                    <option value="salary">Salary</option>
                                    <option value="benefits">Benefits</option>
                                    <option value="management_action">Management Action</option>
                                    <option value="safety">Safety</option>
                                    <option value="bullying">Bullying</option>
                                    <option value="other">Other</option>
                                </select>
                                {errors.grievance_category && <p className="text-red-500 text-sm mt-1">{errors.grievance_category}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Description *
                                </label>
                                <textarea
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={6}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    required
                                />
                                {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Incident Date
                                </label>
                                <input
                                    type="date"
                                    value={data.incident_date}
                                    onChange={(e) => setData('incident_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                />
                                {errors.incident_date && <p className="text-red-500 text-sm mt-1">{errors.incident_date}</p>}
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-4">
                            <Link href="/zambian-hr/grievances">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Submitting...' : 'File Grievance'}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </SectionLayout>
    );
}

