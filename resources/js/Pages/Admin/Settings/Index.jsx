import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { router } from '@inertiajs/react';
import { Settings, Save } from 'lucide-react';
import { useState } from 'react';

export default function Index({ settings }) {
    const [formData, setFormData] = useState(() => {
        const data = {};
        if (settings && typeof settings === 'object') {
            Object.entries(settings).forEach(([group, groupSettings]) => {
                if (Array.isArray(groupSettings)) {
                    groupSettings.forEach((setting) => {
                        if (setting && setting.key) {
                            data[setting.key] = setting.value !== null && setting.value !== undefined ? setting.value : '';
                        }
                    });
                }
            });
        }
        return data;
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        router.put('/admin/settings', { settings: formData }, {
            preserveScroll: true,
        });
    };

    const handleChange = (key, value) => {
        setFormData((prev) => ({ ...prev, [key]: value }));
    };

    const groupLabels = {
        ai: 'AI Settings',
        email: 'Email Settings',
        features: 'Feature Flags',
        billing: 'Billing',
        general: 'General',
    };

    return (
        <AdminLayout title="Platform Settings">
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Platform Settings</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Configure global platform settings
                    </p>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="space-y-6">
                        {settings && typeof settings === 'object' && Object.entries(settings).map(([group, groupSettings]) => (
                            <Card key={group} className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                    {groupLabels[group] || group}
                                </h3>
                                <div className="space-y-4">
                                    {Array.isArray(groupSettings) && groupSettings.map((setting) => (
                                        <div key={setting.key}>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                {setting.label || setting.key}
                                            </label>
                                            {setting.description && (
                                                <p className="text-xs text-gray-500 mb-2">
                                                    {setting.description}
                                                </p>
                                            )}
                                            {setting.type === 'boolean' ? (
                                                <label className="flex items-center space-x-2">
                                                    <input
                                                        type="checkbox"
                                                        checked={formData[setting.key] === 'true' || formData[setting.key] === true || formData[setting.key] === '1'}
                                                        onChange={(e) =>
                                                            handleChange(
                                                                setting.key,
                                                                e.target.checked ? 'true' : 'false'
                                                            )
                                                        }
                                                        className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                    />
                                                    <span className="text-sm text-gray-700">
                                                        Enable {setting.label || setting.key}
                                                    </span>
                                                </label>
                                            ) : setting.type === 'encrypted' ? (
                                                <input
                                                    type="password"
                                                    value={formData[setting.key] || ''}
                                                    onChange={(e) =>
                                                        handleChange(setting.key, e.target.value)
                                                    }
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                    placeholder={formData[setting.key] ? 'Enter new value to update' : 'Enter value...'}
                                                />
                                            ) : setting.type === 'integer' ? (
                                                <input
                                                    type="number"
                                                    value={formData[setting.key] || ''}
                                                    onChange={(e) =>
                                                        handleChange(setting.key, e.target.value)
                                                    }
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                />
                                            ) : (
                                                <input
                                                    type="text"
                                                    value={formData[setting.key] || ''}
                                                    onChange={(e) =>
                                                        handleChange(setting.key, e.target.value)
                                                    }
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                />
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </Card>
                        ))}
                    </div>

                    <div className="mt-6 flex justify-end">
                        <Button type="submit">
                            <Save className="w-4 h-4 mr-2" />
                            Save Settings
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

