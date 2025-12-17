import AdminLayout from '@/Layouts/AdminLayout';
import { Card } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { useState } from 'react';
import { router } from '@inertiajs/react';
import { 
    CheckCircle2, 
    XCircle, 
    AlertCircle,
    Clock,
    Play,
    FileText,
    Code,
    Shield,
    Server,
    BookOpen,
    ChevronDown,
    ChevronRight,
    Loader2,
    RefreshCw,
    CheckSquare,
    Square,
    AlertTriangle,
    Target,
    Package,
    Beaker
} from 'lucide-react';

export default function Testing({ existingTests, requiredTests, completeness, lastTestRun }) {
    const [expandedSections, setExpandedSections] = useState({});
    const [runningTests, setRunningTests] = useState(false);
    const [testOutput, setTestOutput] = useState(null);
    const [activeTab, setActiveTab] = useState('tests');

    const toggleSection = (section) => {
        setExpandedSections(prev => ({
            ...prev,
            [section]: !prev[section]
        }));
    };

    const runTests = async (type = 'all') => {
        setRunningTests(true);
        setTestOutput(null);
        
        try {
            const response = await fetch('/admin/testing/run', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ type }),
            });
            const data = await response.json();
            setTestOutput(data);
        } catch (error) {
            setTestOutput({ success: false, error: error.message });
        } finally {
            setRunningTests(false);
        }
    };

    const getStatusIcon = (status) => {
        switch (status) {
            case 'complete':
            case 'created':
                return <CheckCircle2 className="w-5 h-5 text-green-500" />;
            case 'incomplete':
            case 'pending':
                return <XCircle className="w-5 h-5 text-red-500" />;
            case 'partial':
            case 'placeholder':
                return <AlertCircle className="w-5 h-5 text-yellow-500" />;
            case 'missing':
                return <AlertTriangle className="w-5 h-5 text-red-500" />;
            default:
                return <Clock className="w-5 h-5 text-gray-400" />;
        }
    };

    const getPriorityBadge = (priority) => {
        const colors = {
            critical: 'bg-red-100 text-red-800 border-red-200',
            high: 'bg-orange-100 text-orange-800 border-orange-200',
            medium: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            low: 'bg-gray-100 text-gray-800 border-gray-200',
        };
        return (
            <span className={`px-2 py-1 text-xs font-medium rounded-full border ${colors[priority] || colors.medium}`}>
                {priority}
            </span>
        );
    };

    const countTests = (tests) => {
        let total = 0;
        let created = 0;
        Object.values(tests).forEach(testType => {
            testType.forEach(test => {
                total++;
                if (test.status === 'created') created++;
            });
        });
        return { total, created };
    };

    const existingTestCount = Object.values(existingTests).reduce((sum, tests) => sum + tests.length, 0);
    const { total: requiredTotal, created: requiredCreated } = countTests(requiredTests);

    const overallCompleteness = Math.round(
        completeness.modules.reduce((sum, m) => sum + m.percentage, 0) / completeness.modules.length
    );

    const tabs = [
        { id: 'tests', name: 'Tests', icon: Beaker },
        { id: 'completeness', name: 'Completeness', icon: Target },
        { id: 'security', name: 'Security', icon: Shield },
        { id: 'deployment', name: 'Deployment', icon: Server },
        { id: 'documentation', name: 'Documentation', icon: BookOpen },
    ];

    return (
        <AdminLayout title="Testing & Completeness">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Testing & Completeness</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Monitor test coverage and application completeness
                        </p>
                    </div>
                    <Button
                        onClick={() => router.reload()}
                        variant="outline"
                        className="flex items-center gap-2"
                    >
                        <RefreshCw className="w-4 h-4" />
                        Refresh
                    </Button>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Overall Completeness</p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">{overallCompleteness}%</p>
                            </div>
                            <div className="p-3 rounded-lg bg-teal-50">
                                <Target className="w-6 h-6 text-teal-600" />
                            </div>
                        </div>
                        <div className="mt-4">
                            <div className="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    className="bg-teal-500 h-2 rounded-full transition-all duration-500"
                                    style={{ width: `${overallCompleteness}%` }}
                                />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Existing Tests</p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">{existingTestCount}</p>
                            </div>
                            <div className="p-3 rounded-lg bg-green-50">
                                <CheckCircle2 className="w-6 h-6 text-green-600" />
                            </div>
                        </div>
                        <p className="mt-2 text-sm text-gray-500">
                            Across {Object.keys(existingTests).filter(k => existingTests[k].length > 0).length} test suites
                        </p>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Required Tests</p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">
                                    {requiredCreated}/{requiredTotal}
                                </p>
                            </div>
                            <div className="p-3 rounded-lg bg-orange-50">
                                <Beaker className="w-6 h-6 text-orange-600" />
                            </div>
                        </div>
                        <p className="mt-2 text-sm text-gray-500">
                            {requiredTotal - requiredCreated} tests needed
                        </p>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Last Test Run</p>
                                <p className="mt-2 text-lg font-bold text-gray-900">
                                    {lastTestRun ? new Date(lastTestRun.date).toLocaleDateString() : 'Never'}
                                </p>
                            </div>
                            <div className="p-3 rounded-lg bg-purple-50">
                                <Clock className="w-6 h-6 text-purple-600" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Tabs */}
                <div className="border-b border-gray-200">
                    <nav className="-mb-px flex space-x-8">
                        {tabs.map((tab) => {
                            const Icon = tab.icon;
                            return (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
                                        activeTab === tab.id
                                            ? 'border-teal-500 text-teal-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    <Icon className="w-4 h-4" />
                                    {tab.name}
                                </button>
                            );
                        })}
                    </nav>
                </div>

                {/* Tests Tab */}
                {activeTab === 'tests' && (
                    <div className="space-y-6">
                        {/* Run Tests */}
                        <Card className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-semibold text-gray-900">Run Tests</h3>
                                <div className="flex gap-2">
                                    <Button
                                        onClick={() => runTests('unit')}
                                        disabled={runningTests}
                                        variant="outline"
                                        size="sm"
                                    >
                                        {runningTests ? <Loader2 className="w-4 h-4 animate-spin" /> : <Play className="w-4 h-4" />}
                                        <span className="ml-2">Unit Tests</span>
                                    </Button>
                                    <Button
                                        onClick={() => runTests('feature')}
                                        disabled={runningTests}
                                        variant="outline"
                                        size="sm"
                                    >
                                        {runningTests ? <Loader2 className="w-4 h-4 animate-spin" /> : <Play className="w-4 h-4" />}
                                        <span className="ml-2">Feature Tests</span>
                                    </Button>
                                    <Button
                                        onClick={() => runTests('all')}
                                        disabled={runningTests}
                                        size="sm"
                                    >
                                        {runningTests ? <Loader2 className="w-4 h-4 animate-spin" /> : <Play className="w-4 h-4" />}
                                        <span className="ml-2">All Tests</span>
                                    </Button>
                                </div>
                            </div>
                            {testOutput && (
                                <div className={`p-4 rounded-lg ${testOutput.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}`}>
                                    <pre className="text-xs font-mono whitespace-pre-wrap overflow-auto max-h-64">
                                        {testOutput.output || testOutput.error}
                                    </pre>
                                </div>
                            )}
                        </Card>

                        {/* Existing Tests */}
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Existing Tests</h3>
                            <div className="space-y-4">
                                {Object.entries(existingTests).map(([type, tests]) => (
                                    tests.length > 0 && (
                                        <div key={type} className="border rounded-lg">
                                            <button
                                                onClick={() => toggleSection(`existing-${type}`)}
                                                className="w-full flex items-center justify-between p-4 hover:bg-gray-50"
                                            >
                                                <div className="flex items-center gap-3">
                                                    {expandedSections[`existing-${type}`] ? (
                                                        <ChevronDown className="w-5 h-5 text-gray-400" />
                                                    ) : (
                                                        <ChevronRight className="w-5 h-5 text-gray-400" />
                                                    )}
                                                    <span className="font-medium capitalize">{type} Tests</span>
                                                    <span className="text-sm text-gray-500">({tests.length} files)</span>
                                                </div>
                                                <span className="text-sm text-green-600 font-medium">
                                                    {tests.reduce((sum, t) => sum + t.methodCount, 0)} test methods
                                                </span>
                                            </button>
                                            {expandedSections[`existing-${type}`] && (
                                                <div className="border-t divide-y">
                                                    {tests.map((test, idx) => (
                                                        <div key={idx} className="p-4 bg-gray-50">
                                                            <div className="flex items-center justify-between">
                                                                <div className="flex items-center gap-2">
                                                                    <Code className="w-4 h-4 text-gray-400" />
                                                                    <span className="font-medium text-sm">{test.name}</span>
                                                                </div>
                                                                <span className="text-xs text-gray-500">{test.methodCount} tests</span>
                                                            </div>
                                                            <p className="text-xs text-gray-400 mt-1 ml-6">{test.file}</p>
                                                            {test.methods.length > 0 && (
                                                                <div className="mt-2 ml-6 flex flex-wrap gap-1">
                                                                    {test.methods.map((method, i) => (
                                                                        <span key={i} className="px-2 py-0.5 text-xs bg-gray-200 rounded">
                                                                            {method}
                                                                        </span>
                                                                    ))}
                                                                </div>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    )
                                ))}
                            </div>
                        </Card>

                        {/* Required Tests */}
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Required Tests</h3>
                            <p className="text-sm text-gray-500 mb-4">
                                Tests that should be created for comprehensive coverage
                            </p>
                            <div className="space-y-4">
                                {Object.entries(requiredTests).map(([type, tests]) => (
                                    <div key={type} className="border rounded-lg">
                                        <button
                                            onClick={() => toggleSection(`required-${type}`)}
                                            className="w-full flex items-center justify-between p-4 hover:bg-gray-50"
                                        >
                                            <div className="flex items-center gap-3">
                                                {expandedSections[`required-${type}`] ? (
                                                    <ChevronDown className="w-5 h-5 text-gray-400" />
                                                ) : (
                                                    <ChevronRight className="w-5 h-5 text-gray-400" />
                                                )}
                                                <span className="font-medium capitalize">{type} Tests</span>
                                                <span className="text-sm text-gray-500">({tests.length} tests)</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm text-green-600">
                                                    {tests.filter(t => t.status === 'created').length} created
                                                </span>
                                                <span className="text-gray-300">|</span>
                                                <span className="text-sm text-red-600">
                                                    {tests.filter(t => t.status === 'pending').length} pending
                                                </span>
                                            </div>
                                        </button>
                                        {expandedSections[`required-${type}`] && (
                                            <div className="border-t">
                                                <table className="w-full">
                                                    <thead className="bg-gray-50">
                                                        <tr>
                                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Test Name</th>
                                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y">
                                                        {tests.map((test, idx) => (
                                                            <tr key={idx} className="hover:bg-gray-50">
                                                                <td className="px-4 py-3">
                                                                    {getStatusIcon(test.status)}
                                                                </td>
                                                                <td className="px-4 py-3 font-medium text-sm">{test.name}</td>
                                                                <td className="px-4 py-3 text-sm text-gray-600">{test.category}</td>
                                                                <td className="px-4 py-3">{getPriorityBadge(test.priority)}</td>
                                                                <td className="px-4 py-3 text-sm text-gray-500">{test.description}</td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>
                )}

                {/* Completeness Tab */}
                {activeTab === 'completeness' && (
                    <div className="space-y-6">
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Module Completeness</h3>
                            <div className="space-y-4">
                                {completeness.modules.map((module, idx) => (
                                    <div key={idx} className="border rounded-lg">
                                        <button
                                            onClick={() => toggleSection(`module-${idx}`)}
                                            className="w-full flex items-center justify-between p-4 hover:bg-gray-50"
                                        >
                                            <div className="flex items-center gap-3">
                                                {expandedSections[`module-${idx}`] ? (
                                                    <ChevronDown className="w-5 h-5 text-gray-400" />
                                                ) : (
                                                    <ChevronRight className="w-5 h-5 text-gray-400" />
                                                )}
                                                <Package className="w-5 h-5 text-gray-400" />
                                                <span className="font-medium">{module.name}</span>
                                            </div>
                                            <div className="flex items-center gap-4">
                                                <div className="w-32 bg-gray-200 rounded-full h-2">
                                                    <div 
                                                        className={`h-2 rounded-full ${
                                                            module.percentage >= 90 ? 'bg-green-500' :
                                                            module.percentage >= 70 ? 'bg-yellow-500' : 'bg-red-500'
                                                        }`}
                                                        style={{ width: `${module.percentage}%` }}
                                                    />
                                                </div>
                                                <span className={`text-sm font-medium ${
                                                    module.percentage >= 90 ? 'text-green-600' :
                                                    module.percentage >= 70 ? 'text-yellow-600' : 'text-red-600'
                                                }`}>
                                                    {module.percentage}%
                                                </span>
                                            </div>
                                        </button>
                                        {expandedSections[`module-${idx}`] && (
                                            <div className="border-t p-4 bg-gray-50">
                                                <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                    {module.items.map((item, i) => (
                                                        <div key={i} className="flex items-center gap-2 text-sm">
                                                            {getStatusIcon(item.status)}
                                                            <span className={item.status === 'complete' ? 'text-gray-700' : 'text-gray-500'}>
                                                                {item.name}
                                                            </span>
                                                            {item.note && (
                                                                <span className="text-xs text-gray-400">({item.note})</span>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </Card>

                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Feature Completeness</h3>
                            <div className="space-y-4">
                                {completeness.features.map((feature, idx) => (
                                    <div key={idx} className="border rounded-lg">
                                        <button
                                            onClick={() => toggleSection(`feature-${idx}`)}
                                            className="w-full flex items-center justify-between p-4 hover:bg-gray-50"
                                        >
                                            <div className="flex items-center gap-3">
                                                {expandedSections[`feature-${idx}`] ? (
                                                    <ChevronDown className="w-5 h-5 text-gray-400" />
                                                ) : (
                                                    <ChevronRight className="w-5 h-5 text-gray-400" />
                                                )}
                                                <span className="font-medium">{feature.name}</span>
                                            </div>
                                            <div className="flex items-center gap-4">
                                                <div className="w-32 bg-gray-200 rounded-full h-2">
                                                    <div 
                                                        className={`h-2 rounded-full ${
                                                            feature.percentage >= 90 ? 'bg-green-500' :
                                                            feature.percentage >= 70 ? 'bg-yellow-500' : 'bg-red-500'
                                                        }`}
                                                        style={{ width: `${feature.percentage}%` }}
                                                    />
                                                </div>
                                                <span className={`text-sm font-medium ${
                                                    feature.percentage >= 90 ? 'text-green-600' :
                                                    feature.percentage >= 70 ? 'text-yellow-600' : 'text-red-600'
                                                }`}>
                                                    {feature.percentage}%
                                                </span>
                                            </div>
                                        </button>
                                        {expandedSections[`feature-${idx}`] && (
                                            <div className="border-t p-4 bg-gray-50">
                                                <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                    {feature.items.map((item, i) => (
                                                        <div key={i} className="flex items-center gap-2 text-sm">
                                                            {getStatusIcon(item.status)}
                                                            <span className={item.status === 'complete' ? 'text-gray-700' : 'text-gray-500'}>
                                                                {item.name}
                                                            </span>
                                                            {item.note && (
                                                                <span className="text-xs text-gray-400">({item.note})</span>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>
                )}

                {/* Security Tab */}
                {activeTab === 'security' && (
                    <Card className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Security Checklist</h3>
                        <div className="space-y-3">
                            {completeness.security.map((item, idx) => (
                                <div key={idx} className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                                    <div className="flex items-center gap-3">
                                        {getStatusIcon(item.status)}
                                        <span className="font-medium text-sm">{item.name}</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {item.note && (
                                            <span className="text-xs text-gray-500">{item.note}</span>
                                        )}
                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                            item.status === 'complete' ? 'bg-green-100 text-green-800' :
                                            item.status === 'partial' ? 'bg-yellow-100 text-yellow-800' :
                                            item.status === 'pending' ? 'bg-blue-100 text-blue-800' :
                                            'bg-red-100 text-red-800'
                                        }`}>
                                            {item.status}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}

                {/* Deployment Tab */}
                {activeTab === 'deployment' && (
                    <Card className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Deployment Checklist</h3>
                        <div className="space-y-3">
                            {completeness.deployment.map((item, idx) => (
                                <div key={idx} className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                                    <div className="flex items-center gap-3">
                                        {getStatusIcon(item.status)}
                                        <span className="font-medium text-sm">{item.name}</span>
                                    </div>
                                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                        item.status === 'complete' ? 'bg-green-100 text-green-800' :
                                        item.status === 'missing' ? 'bg-red-100 text-red-800' :
                                        'bg-gray-100 text-gray-800'
                                    }`}>
                                        {item.status}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}

                {/* Documentation Tab */}
                {activeTab === 'documentation' && (
                    <Card className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Documentation Status</h3>
                        <div className="space-y-3">
                            {completeness.documentation.map((item, idx) => (
                                <div key={idx} className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                                    <div className="flex items-center gap-3">
                                        {getStatusIcon(item.status)}
                                        <FileText className="w-4 h-4 text-gray-400" />
                                        <span className="font-medium text-sm">{item.name}</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {item.note && (
                                            <span className="text-xs text-gray-500">{item.note}</span>
                                        )}
                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                            item.status === 'complete' ? 'bg-green-100 text-green-800' :
                                            item.status === 'incomplete' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-red-100 text-red-800'
                                        }`}>
                                            {item.status}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}


