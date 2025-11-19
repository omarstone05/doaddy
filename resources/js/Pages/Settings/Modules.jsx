import { Head, router, usePage } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Package, CheckCircle2, XCircle, Info, Settings as SettingsIcon, Power } from 'lucide-react';
import { useState } from 'react';

export default function ModulesSettings({ modules }) {
    const { flash } = usePage().props;
    const [togglingModules, setTogglingModules] = useState({});

    const handleToggle = (moduleName) => {
        setTogglingModules(prev => ({ ...prev, [moduleName]: true }));
        
        router.post(`/modules/${moduleName}/toggle`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Reload the page to refresh navigation and module state
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            },
            onFinish: () => {
                setTogglingModules(prev => ({ ...prev, [moduleName]: false }));
            },
        });
    };

    const successMessage = flash?.message;
    const errorMessage = flash?.error;

    return (
        <SectionLayout sectionName="Settings">
            <Head title="Modules" />
            <div className="max-w-6xl mx-auto">
                <div className="mb-6">
                    <div className="flex items-center gap-3 mb-2">
                        <Package className="h-6 w-6 text-teal-600" />
                        <h1 className="text-3xl font-bold text-gray-900">Modules</h1>
                    </div>
                    <p className="text-gray-500 mt-1">Enable or disable modules to customize your Addy experience</p>
                </div>

                {successMessage && (
                    <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p className="text-green-800 font-medium">{successMessage}</p>
                    </div>
                )}

                {errorMessage && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p className="text-red-800 font-medium">{errorMessage}</p>
                    </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {modules.map((module) => (
                        <Card key={module.name} className="p-6 hover:shadow-lg transition-shadow">
                            <div className="flex items-start justify-between mb-4">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-2">
                                        <h3 className="text-lg font-semibold text-gray-900">
                                            {module.display_name}
                                        </h3>
                                        {module.enabled ? (
                                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                                        ) : (
                                            <XCircle className="h-5 w-5 text-gray-400" />
                                        )}
                                    </div>
                                    <p className="text-sm text-gray-600 mb-2">{module.description}</p>
                                    <div className="flex items-center gap-4 text-xs text-gray-500">
                                        <span>v{module.version}</span>
                                        <span>by {module.author}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Features */}
                            {module.features && module.features.length > 0 && (
                                <div className="mb-4">
                                    <p className="text-xs font-medium text-gray-700 mb-2">Features:</p>
                                    <div className="flex flex-wrap gap-1">
                                        {module.features.slice(0, 3).map((feature, idx) => (
                                            <span
                                                key={idx}
                                                className="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"
                                            >
                                                {feature.replace(/_/g, ' ')}
                                            </span>
                                        ))}
                                        {module.features.length > 3 && (
                                            <span className="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs">
                                                +{module.features.length - 3} more
                                            </span>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Suitable For */}
                            {module.suitable_for && module.suitable_for.length > 0 && (
                                <div className="mb-4">
                                    <p className="text-xs font-medium text-gray-700 mb-2">Suitable for:</p>
                                    <div className="flex flex-wrap gap-1">
                                        {module.suitable_for.slice(0, 3).map((industry, idx) => (
                                            <span
                                                key={idx}
                                                className="px-2 py-1 bg-teal-50 text-teal-700 rounded text-xs"
                                            >
                                                {industry}
                                            </span>
                                        ))}
                                        {module.suitable_for.length > 3 && (
                                            <span className="px-2 py-1 bg-teal-50 text-teal-700 rounded text-xs">
                                                +{module.suitable_for.length - 3} more
                                            </span>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Dependencies */}
                            {module.dependencies && module.dependencies.length > 0 && (
                                <div className="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                    <div className="flex items-start gap-2">
                                        <Info className="h-4 w-4 text-yellow-600 mt-0.5 flex-shrink-0" />
                                        <div>
                                            <p className="text-xs font-medium text-yellow-800">Dependencies:</p>
                                            <p className="text-xs text-yellow-700">
                                                {module.dependencies.join(', ')}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Toggle Button */}
                            <div className="pt-4 border-t border-gray-200">
                                <Button
                                    onClick={() => handleToggle(module.name)}
                                    disabled={togglingModules[module.name]}
                                    variant={module.enabled ? 'secondary' : 'primary'}
                                    className="w-full"
                                >
                                    <Power className="h-4 w-4 mr-2" />
                                    {togglingModules[module.name]
                                        ? 'Updating...'
                                        : module.enabled
                                        ? 'Disable Module'
                                        : 'Enable Module'}
                                </Button>
                            </div>
                        </Card>
                    ))}
                </div>

                {modules.length === 0 && (
                    <Card className="p-12 text-center">
                        <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No Modules Found</h3>
                        <p className="text-gray-500">Modules will appear here once they are installed.</p>
                    </Card>
                )}
            </div>
        </SectionLayout>
    );
}

