import { Head, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function LeadsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        company_name: '',
        job_title: '',
        email: '',
        phone: '',
        whatsapp_number: '',
        lead_source: 'other',
        lead_status: 'new',
        notes: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/crm/leads');
    };

    return (
        <SectionLayout sectionName="CRM">
            <Head title="Create Lead" />
            <div className="max-w-2xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit('/crm/leads')}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back to Leads
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Create Lead</h1>
                    <p className="text-gray-500 mt-1">Add a new lead to your CRM</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="first_name" className="block text-sm font-medium text-gray-700 mb-2">
                                    First Name *
                                </label>
                                <input
                                    id="first_name"
                                    type="text"
                                    value={data.first_name}
                                    onChange={(e) => setData('first_name', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.first_name && <p className="mt-1 text-sm text-red-600">{errors.first_name}</p>}
                            </div>
                            <div>
                                <label htmlFor="last_name" className="block text-sm font-medium text-gray-700 mb-2">
                                    Last Name *
                                </label>
                                <input
                                    id="last_name"
                                    type="text"
                                    value={data.last_name}
                                    onChange={(e) => setData('last_name', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.last_name && <p className="mt-1 text-sm text-red-600">{errors.last_name}</p>}
                            </div>
                        </div>

                        <div>
                            <label htmlFor="company_name" className="block text-sm font-medium text-gray-700 mb-2">
                                Company Name
                            </label>
                            <input
                                id="company_name"
                                type="text"
                                value={data.company_name}
                                onChange={(e) => setData('company_name', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label htmlFor="job_title" className="block text-sm font-medium text-gray-700 mb-2">
                                Job Title
                            </label>
                            <input
                                id="job_title"
                                type="text"
                                value={data.job_title}
                                onChange={(e) => setData('job_title', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                                {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                            </div>
                            <div>
                                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-2">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    type="text"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <div>
                            <label htmlFor="whatsapp_number" className="block text-sm font-medium text-gray-700 mb-2">
                                WhatsApp Number
                            </label>
                            <input
                                id="whatsapp_number"
                                type="text"
                                value={data.whatsapp_number}
                                onChange={(e) => setData('whatsapp_number', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="+260 977 123456"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="lead_source" className="block text-sm font-medium text-gray-700 mb-2">
                                    Lead Source *
                                </label>
                                <select
                                    id="lead_source"
                                    value={data.lead_source}
                                    onChange={(e) => setData('lead_source', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="website">Website</option>
                                    <option value="referral">Referral</option>
                                    <option value="social_media">Social Media</option>
                                    <option value="cold_call">Cold Call</option>
                                    <option value="event">Event</option>
                                    <option value="partner">Partner</option>
                                    <option value="advertisement">Advertisement</option>
                                    <option value="walk_in">Walk In</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label htmlFor="lead_status" className="block text-sm font-medium text-gray-700 mb-2">
                                    Status *
                                </label>
                                <select
                                    id="lead_status"
                                    value={data.lead_status}
                                    onChange={(e) => setData('lead_status', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="unqualified">Unqualified</option>
                                    <option value="converted">Converted</option>
                                    <option value="lost">Lost</option>
                                    <option value="nurturing">Nurturing</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-2">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={4}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="Additional information about this lead..."
                            />
                        </div>

                        <div className="flex gap-4 pt-4 border-t border-gray-200">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Create Lead
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => router.visit('/crm/leads')}
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </SectionLayout>
    );
}


