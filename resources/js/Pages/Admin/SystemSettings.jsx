import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import axios from 'axios';

export default function SystemSettings({ settings }) {
    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState(null);

    const { data, setData, post, processing, errors } = useForm({
        ai_provider: settings.ai_provider,
        openai_api_key: settings.openai_api_key || '',
        openai_model: settings.openai_model,
        anthropic_api_key: settings.anthropic_api_key || '',
        anthropic_model: settings.anthropic_model,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/system-settings');
    };

    const testConnection = async () => {
        setTesting(true);
        setTestResult(null);

        try {
            const response = await axios.post('/admin/system-settings/test');
            setTestResult({
                success: true,
                message: response.data.message,
                response: response.data.response,
            });
        } catch (error) {
            setTestResult({
                success: false,
                message: error.response?.data?.message || 'Connection failed',
            });
        } finally {
            setTesting(false);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="System Settings" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="bg-white rounded-lg shadow p-6">
                        <h2 className="text-2xl font-bold text-gray-900 mb-6">
                            System Settings - AI Configuration
                        </h2>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* AI Provider Selection */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    AI Provider
                                </label>
                                <select
                                    value={data.ai_provider}
                                    onChange={(e) => setData('ai_provider', e.target.value)}
                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="openai">OpenAI (GPT-4)</option>
                                    <option value="anthropic">Anthropic (Claude)</option>
                                </select>
                            </div>

                            {/* OpenAI Settings */}
                            <div className="border-t pt-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                    OpenAI Settings
                                </h3>

                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            OpenAI API Key
                                        </label>
                                        <input
                                            type="password"
                                            value={data.openai_api_key}
                                            onChange={(e) => setData('openai_api_key', e.target.value)}
                                            placeholder="sk-..."
                                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <p className="mt-1 text-sm text-gray-500">
                                            Get your API key from{' '}
                                            <a
                                                href="https://platform.openai.com/api-keys"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-blue-600 hover:text-blue-700"
                                            >
                                                platform.openai.com
                                            </a>
                                        </p>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Model
                                        </label>
                                        <select
                                            value={data.openai_model}
                                            onChange={(e) => setData('openai_model', e.target.value)}
                                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="gpt-4o">GPT-4o (Recommended)</option>
                                            <option value="gpt-4o-mini">GPT-4o Mini (Faster/Cheaper)</option>
                                            <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {/* Anthropic Settings */}
                            <div className="border-t pt-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                    Anthropic Settings
                                </h3>

                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Anthropic API Key
                                        </label>
                                        <input
                                            type="password"
                                            value={data.anthropic_api_key}
                                            onChange={(e) => setData('anthropic_api_key', e.target.value)}
                                            placeholder="sk-ant-..."
                                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <p className="mt-1 text-sm text-gray-500">
                                            Get your API key from{' '}
                                            <a
                                                href="https://console.anthropic.com"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-blue-600 hover:text-blue-700"
                                            >
                                                console.anthropic.com
                                            </a>
                                        </p>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Model
                                        </label>
                                        <select
                                            value={data.anthropic_model}
                                            onChange={(e) => setData('anthropic_model', e.target.value)}
                                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="claude-sonnet-4-20250514">
                                                Claude Sonnet 4.5 (Recommended)
                                            </option>
                                            <option value="claude-opus-4-20250514">
                                                Claude Opus 4 (Most Capable)
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {/* Test Connection */}
                            {testResult && (
                                <div
                                    className={`p-4 rounded-md ${
                                        testResult.success
                                            ? 'bg-green-50 text-green-800'
                                            : 'bg-red-50 text-red-800'
                                    }`}
                                >
                                    <p className="font-semibold">{testResult.message}</p>
                                    {testResult.response && (
                                        <p className="mt-2 text-sm">{testResult.response}</p>
                                    )}
                                </div>
                            )}

                            {/* Buttons */}
                            <div className="flex items-center gap-4 pt-6">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {processing ? 'Saving...' : 'Save Settings'}
                                </button>

                                <button
                                    type="button"
                                    onClick={testConnection}
                                    disabled={testing}
                                    className="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 disabled:opacity-50"
                                >
                                    {testing ? 'Testing...' : 'Test Connection'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

