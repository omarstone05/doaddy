import { Head, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Save, Building2 } from 'lucide-react';

export default function SettingsIndex({ organization }) {
    const { data, setData, put, processing, errors } = useForm({
        name: organization.name || '',
        slug: organization.slug || '',
        business_type: organization.business_type || '',
        industry: organization.industry || '',
        tone_preference: organization.tone_preference || '',
        currency: organization.currency || 'ZMW',
        timezone: organization.timezone || 'Africa/Lusaka',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put('/settings');
    };

    return (
        <SectionLayout sectionName="Settings">
            <Head title="Settings" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <div className="flex items-center gap-3 mb-2">
                        <Building2 className="h-6 w-6 text-teal-600" />
                        <h1 className="text-3xl font-bold text-gray-900">Organization Settings</h1>
                    </div>
                    <p className="text-gray-500 mt-1">Manage your organization's settings and preferences</p>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Organization Name <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                                <input
                                    type="text"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="organization-slug"
                                />
                                {errors.slug && <p className="mt-1 text-sm text-red-600">{errors.slug}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Business Type</label>
                                <input
                                    type="text"
                                    value={data.business_type}
                                    onChange={(e) => setData('business_type', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="e.g., Retail, Service, Manufacturing"
                                />
                                {errors.business_type && <p className="mt-1 text-sm text-red-600">{errors.business_type}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                                <input
                                    type="text"
                                    value={data.industry}
                                    onChange={(e) => setData('industry', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="e.g., Technology, Healthcare, Retail"
                                />
                                {errors.industry && <p className="mt-1 text-sm text-red-600">{errors.industry}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                <select
                                    value={data.currency}
                                    onChange={(e) => setData('currency', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="ZMW">ZMW - Zambian Kwacha</option>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                    <option value="ZAR">ZAR - South African Rand</option>
                                    <option value="KES">KES - Kenyan Shilling</option>
                                    <option value="NGN">NGN - Nigerian Naira</option>
                                    <option value="GHS">GHS - Ghanaian Cedi</option>
                                </select>
                                {errors.currency && <p className="mt-1 text-sm text-red-600">{errors.currency}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                                <select
                                    value={data.timezone}
                                    onChange={(e) => setData('timezone', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="Africa/Lusaka">Africa/Lusaka (CAT)</option>
                                    <option value="Africa/Johannesburg">Africa/Johannesburg (SAST)</option>
                                    <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
                                    <option value="Africa/Lagos">Africa/Lagos (WAT)</option>
                                    <option value="Africa/Accra">Africa/Accra (GMT)</option>
                                    <option value="UTC">UTC</option>
                                </select>
                                {errors.timezone && <p className="mt-1 text-sm text-red-600">{errors.timezone}</p>}
                            </div>

                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Tone Preference</label>
                                <select
                                    value={data.tone_preference}
                                    onChange={(e) => setData('tone_preference', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="">Select tone</option>
                                    <option value="formal">Formal</option>
                                    <option value="casual">Casual</option>
                                    <option value="friendly">Friendly</option>
                                    <option value="professional">Professional</option>
                                </select>
                                {errors.tone_preference && <p className="mt-1 text-sm text-red-600">{errors.tone_preference}</p>}
                            </div>
                        </div>

                        <div className="flex gap-3 pt-4 border-t border-gray-200">
                            <Button type="submit" disabled={processing}>
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Saving...' : 'Save Settings'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </SectionLayout>
    );
}

