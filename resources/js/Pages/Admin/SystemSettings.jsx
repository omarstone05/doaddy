import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { router } from '@inertiajs/react';
import { Settings, Save, TestTube } from 'lucide-react';
import { useState } from 'react';

export default function SystemSettings({ settings }) {
    const [formData, setFormData] = useState({
        ai_provider: settings.ai_provider || 'openai',
        openai_api_key: settings.openai_api_key || '',
        openai_model: settings.openai_model || 'gpt-4o',
        anthropic_api_key: settings.anthropic_api_key || '',
        anthropic_model: settings.anthropic_model || 'claude-sonnet-4-20250514',
    });
    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState(null);

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post('/admin/system-settings', formData, {
            preserveScroll: true,
        });
    };

    const handleChange = (key, value) => {
        setFormData((prev) => ({ ...prev, [key]: value }));
    };

    const handleTestConnection = async () => {
        setTesting(true);
        setTestResult(null);
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/admin/system-settings/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });
            
            const data = await response.json();
            setTestResult(data);
        } catch (error) {
            setTestResult({
                success: false,
                message: error.message || 'Connection failed. Please check your API keys.',
            });
        } finally {
            setTesting(false);
        }
    };

    return (
        <AdminLayout title="System Settings">
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">System Settings</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Configure AI provider and API settings
                    </p>
                </div>

                <form onSubmit={handleSubmit}>
                    <Card className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            AI Provider Configuration
                        </h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    AI Provider
                                </label>
                                <select
                                    value={formData.ai_provider}
                                    onChange={(e) => handleChange('ai_provider', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="openai">OpenAI</option>
                                    <option value="anthropic">Anthropic</option>
                                </select>
                            </div>

                            {formData.ai_provider === 'openai' && (
                                <>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            OpenAI API Key
                                        </label>
                                        <input
                                            type="password"
                                            value={formData.openai_api_key}
                                            onChange={(e) => handleChange('openai_api_key', e.target.value)}
                                            placeholder={settings.openai_api_key ? 'Enter new key to update' : 'Enter API key'}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                        <p className="mt-1 text-xs text-gray-500">
                                            Leave blank to keep existing key
                                        </p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            OpenAI Model
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.openai_model}
                                            onChange={(e) => handleChange('openai_model', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                    </div>
                                </>
                            )}

                            {formData.ai_provider === 'anthropic' && (
                                <>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Anthropic API Key
                                        </label>
                                        <input
                                            type="password"
                                            value={formData.anthropic_api_key}
                                            onChange={(e) => handleChange('anthropic_api_key', e.target.value)}
                                            placeholder={settings.anthropic_api_key ? 'Enter new key to update' : 'Enter API key'}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                        <p className="mt-1 text-xs text-gray-500">
                                            Leave blank to keep existing key
                                        </p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Anthropic Model
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.anthropic_model}
                                            onChange={(e) => handleChange('anthropic_model', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                    </div>
                                </>
                            )}
                        </div>

                        <div className="mt-6 flex items-center justify-between">
                            <Button
                                type="button"
                                onClick={handleTestConnection}
                                disabled={testing}
                                variant="outline"
                            >
                                <TestTube className="w-4 h-4 mr-2" />
                                {testing ? 'Testing...' : 'Test Connection'}
                            </Button>
                            <Button type="submit">
                                <Save className="w-4 h-4 mr-2" />
                                Save Settings
                            </Button>
                        </div>

                        {testResult && (
                            <div className={`mt-4 p-4 rounded-lg ${
                                testResult.success
                                    ? 'bg-green-50 border border-green-200'
                                    : 'bg-red-50 border border-red-200'
                            }`}>
                                <p className={`text-sm font-medium ${
                                    testResult.success ? 'text-green-800' : 'text-red-800'
                                }`}>
                                    {testResult.success ? '✓' : '✗'} {testResult.message}
                                </p>
                                {testResult.response && (
                                    <p className="mt-2 text-xs text-gray-600">
                                        Response: {testResult.response}
                                    </p>
                                )}
                            </div>
                        )}
                    </Card>
                </form>
            </div>
        </AdminLayout>
    );
}
