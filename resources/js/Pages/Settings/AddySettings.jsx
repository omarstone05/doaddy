import React, { useEffect } from 'react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Head, useForm, router } from '@inertiajs/react';

export default function AddySettings({ settings, userPattern }) {
    const { data, setData, post, processing, errors } = useForm({
        // Cultural settings
        tone: settings?.tone || 'professional',
        enable_predictions: settings?.enable_predictions ?? true,
        enable_proactive_suggestions: settings?.enable_proactive_suggestions ?? true,
        max_daily_suggestions: settings?.max_daily_suggestions || 5,
        quiet_hours_start: settings?.quiet_hours_start || '',
        quiet_hours_end: settings?.quiet_hours_end || '',
        
        // User patterns
        work_style: userPattern?.work_style || 'balanced',
        adhd_mode: userPattern?.adhd_mode || false,
        preferred_task_chunk_size: userPattern?.preferred_task_chunk_size || 3,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/settings/addy', {
            preserveScroll: true,
            onSuccess: () => {
                // Success message is handled by Inertia flash messages
            },
            onError: (errors) => {
                console.error('Settings update errors:', errors);
            },
        });
    };

    return (
        <SectionLayout sectionName="Settings">
            <Head title="Addy Settings" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto px-4">
                    <div className="bg-white rounded-lg shadow p-6">
                        <h2 className="text-2xl font-bold mb-6">Addy Preferences</h2>
                        
                        {errors && Object.keys(errors).length > 0 && (
                            <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                                <p className="text-red-800 font-semibold">Please fix the following errors:</p>
                                <ul className="list-disc list-inside mt-2 text-red-700">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message}</li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-8">
                            {/* Communication Style */}
                            <div>
                                <h3 className="text-lg font-semibold mb-4">Communication Style</h3>
                                
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium mb-2">Tone</label>
                                        <select
                                            value={data.tone}
                                            onChange={(e) => setData('tone', e.target.value)}
                                            className="w-full rounded-md border-gray-300"
                                        >
                                            <option value="professional">Professional</option>
                                            <option value="casual">Casual</option>
                                            <option value="motivational">Motivational</option>
                                            <option value="sassy">Sassy</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium mb-2">Work Style</label>
                                        <select
                                            value={data.work_style}
                                            onChange={(e) => setData('work_style', e.target.value)}
                                            className="w-full rounded-md border-gray-300"
                                        >
                                            <option value="focused">Focused (Deep work)</option>
                                            <option value="balanced">Balanced</option>
                                            <option value="creative">Creative (Flexible)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {/* Intelligence Features */}
                            <div className="border-t pt-6">
                                <h3 className="text-lg font-semibold mb-4">Intelligence Features</h3>
                                
                                <div className="space-y-3">
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={data.enable_predictions}
                                            onChange={(e) => setData('enable_predictions', e.target.checked)}
                                            className="rounded"
                                        />
                                        <span>Enable predictive analytics</span>
                                    </label>

                                    <label className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={data.enable_proactive_suggestions}
                                            onChange={(e) => setData('enable_proactive_suggestions', e.target.checked)}
                                            className="rounded"
                                        />
                                        <span>Enable proactive suggestions</span>
                                    </label>

                                    <label className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={data.adhd_mode}
                                            onChange={(e) => setData('adhd_mode', e.target.checked)}
                                            className="rounded"
                                        />
                                        <span>ADHD-aware mode (breaks up overwhelming tasks)</span>
                                    </label>
                                </div>
                            </div>

                            {/* ADHD Settings */}
                            {data.adhd_mode && (
                                <div className="border-t pt-6">
                                    <h3 className="text-lg font-semibold mb-4">ADHD Mode Settings</h3>
                                    
                                    <div>
                                        <label className="block text-sm font-medium mb-2">
                                            Task chunk size (tasks shown at once)
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="5"
                                            value={data.preferred_task_chunk_size}
                                            onChange={(e) => setData('preferred_task_chunk_size', parseInt(e.target.value))}
                                            className="w-32 rounded-md border-gray-300"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Quiet Hours */}
                            <div className="border-t pt-6">
                                <h3 className="text-lg font-semibold mb-4">Quiet Hours</h3>
                                
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium mb-2">Start</label>
                                        <input
                                            type="time"
                                            value={data.quiet_hours_start || ''}
                                            onChange={(e) => setData('quiet_hours_start', e.target.value)}
                                            className="w-full rounded-md border-gray-300"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium mb-2">End</label>
                                        <input
                                            type="time"
                                            value={data.quiet_hours_end || ''}
                                            onChange={(e) => setData('quiet_hours_end', e.target.value)}
                                            className="w-full rounded-md border-gray-300"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Submit */}
                            <div className="pt-6">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-6 py-2 bg-teal-500 text-white rounded-md hover:bg-teal-600 disabled:opacity-50"
                                >
                                    {processing ? 'Saving...' : 'Save Preferences'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}
