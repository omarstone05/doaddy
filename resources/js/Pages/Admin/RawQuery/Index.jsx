import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { useState } from 'react';
import axios from 'axios';
import { Button } from '@/Components/ui/Button';
import { Database, Play, AlertTriangle, CheckCircle, XCircle, Loader2, Table, Code } from 'lucide-react';

export default function RawQueryIndex({ tableStructures, organizationId }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [showTables, setShowTables] = useState(false);
    const [isWriteMode, setIsWriteMode] = useState(false);
    const [writeConfirm, setWriteConfirm] = useState(false);

    const handleExecute = async () => {
        if (!query.trim()) {
            setError('Please enter a SQL query');
            return;
        }

        setLoading(true);
        setError(null);
        setResults(null);

        try {
            const endpoint = isWriteMode ? '/admin/raw-query/execute-write' : '/admin/raw-query/execute';
            const response = await axios.post(endpoint, {
                query: query.trim(),
                organization_id: organizationId,
                confirm: isWriteMode ? writeConfirm : true,
            });

            setResults(response.data);
            setWriteConfirm(false);
        } catch (err) {
            setError(err.response?.data?.error || err.message || 'Query execution failed');
            setResults(null);
        } finally {
            setLoading(false);
        }
    };

    const handleClear = () => {
        setQuery('');
        setResults(null);
        setError(null);
        setWriteConfirm(false);
    };

    const insertTableName = (tableName) => {
        setQuery(prev => prev + (prev ? ' ' : '') + tableName);
        setShowTables(false);
    };

    const sampleQueries = [
        {
            name: 'List all customers',
            query: 'SELECT * FROM customers LIMIT 10',
        },
        {
            name: 'Count sales by month',
            query: `SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(total_amount) as total 
                    FROM sales 
                    GROUP BY month 
                    ORDER BY month DESC`,
        },
        {
            name: 'Top 10 products by revenue',
            query: `SELECT gs.name, SUM(si.quantity) as quantity, SUM(si.total) as revenue 
                    FROM sale_items si 
                    JOIN goods_and_services gs ON si.goods_service_id = gs.id 
                    GROUP BY gs.id, gs.name 
                    ORDER BY revenue DESC 
                    LIMIT 10`,
        },
        {
            name: 'Recent money movements',
            query: 'SELECT * FROM money_movements ORDER BY transaction_date DESC LIMIT 20',
        },
    ];

    return (
        <AdminLayout title="Raw Query Management">
            <Head title="Raw Query Management - Multi-Tenancy" />
            <div className="p-6">
                <div className="max-w-7xl mx-auto">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center gap-3 mb-2">
                            <Database className="h-8 w-8 text-teal-600" />
                            <h1 className="text-3xl font-bold text-gray-900">Raw Query Management</h1>
                        </div>
                        <p className="text-gray-600">
                            Execute SQL queries with automatic tenant isolation. All queries are automatically scoped to your organization.
                        </p>
                    </div>

                    {/* Warning Banner */}
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div className="flex items-start gap-3">
                            <AlertTriangle className="h-5 w-5 text-yellow-600 mt-0.5" />
                            <div className="flex-1">
                                <h3 className="font-semibold text-yellow-900 mb-1">Safety Features</h3>
                                <ul className="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                                    <li>All queries are automatically scoped to your organization (tenant isolation)</li>
                                    <li>Dangerous operations (DROP, TRUNCATE, ALTER, etc.) are blocked</li>
                                    <li>Write operations require explicit confirmation</li>
                                    <li>All queries are logged for audit purposes</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Query Editor */}
                        <div className="lg:col-span-2 space-y-4">
                            {/* Query Input */}
                            <div className="bg-white rounded-lg border border-gray-200 p-4">
                                <div className="flex items-center justify-between mb-3">
                                    <label className="text-sm font-medium text-gray-700 flex items-center gap-2">
                                        <Code className="h-4 w-4" />
                                        SQL Query
                                    </label>
                                    <div className="flex items-center gap-2">
                                        <label className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                checked={isWriteMode}
                                                onChange={(e) => {
                                                    setIsWriteMode(e.target.checked);
                                                    setWriteConfirm(false);
                                                    setResults(null);
                                                    setError(null);
                                                }}
                                                className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            />
                                            <span className="text-gray-700">Write Mode (INSERT/UPDATE/DELETE)</span>
                                        </label>
                                    </div>
                                </div>
                                <textarea
                                    value={query}
                                    onChange={(e) => setQuery(e.target.value)}
                                    placeholder="Enter your SQL query here...&#10;&#10;Example: SELECT * FROM customers LIMIT 10"
                                    className="w-full h-64 p-3 border border-gray-300 rounded-md font-mono text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    spellCheck={false}
                                />
                                
                                {isWriteMode && (
                                    <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                                        <label className="flex items-start gap-2">
                                            <input
                                                type="checkbox"
                                                checked={writeConfirm}
                                                onChange={(e) => setWriteConfirm(e.target.checked)}
                                                className="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500"
                                            />
                                            <span className="text-sm text-red-800">
                                                I understand that this will modify data in the database. This action cannot be undone.
                                            </span>
                                        </label>
                                    </div>
                                )}

                                <div className="flex items-center gap-3 mt-4">
                                    <Button
                                        onClick={handleExecute}
                                        disabled={loading || (isWriteMode && !writeConfirm) || !query.trim()}
                                        className="flex items-center gap-2"
                                    >
                                        {loading ? (
                                            <>
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                                Executing...
                                            </>
                                        ) : (
                                            <>
                                                <Play className="h-4 w-4" />
                                                Execute Query
                                            </>
                                        )}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={handleClear}
                                        disabled={loading}
                                    >
                                        Clear
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={() => setShowTables(!showTables)}
                                        className="flex items-center gap-2"
                                    >
                                        <Table className="h-4 w-4" />
                                        {showTables ? 'Hide' : 'Show'} Tables
                                    </Button>
                                </div>
                            </div>

                            {/* Sample Queries */}
                            <div className="bg-white rounded-lg border border-gray-200 p-4">
                                <h3 className="text-sm font-medium text-gray-700 mb-3">Sample Queries</h3>
                                <div className="grid grid-cols-2 gap-2">
                                    {sampleQueries.map((sample, index) => (
                                        <button
                                            key={index}
                                            onClick={() => setQuery(sample.query)}
                                            className="text-left p-2 text-sm text-gray-700 hover:bg-gray-50 rounded border border-gray-200 hover:border-teal-300 transition-colors"
                                        >
                                            <div className="font-medium">{sample.name}</div>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Results */}
                            {results && (
                                <div className="bg-white rounded-lg border border-gray-200 p-4">
                                    <div className="flex items-center justify-between mb-3">
                                        <div className="flex items-center gap-2">
                                            <CheckCircle className="h-5 w-5 text-green-600" />
                                            <h3 className="font-semibold text-gray-900">Query Results</h3>
                                        </div>
                                        {results.count !== undefined && (
                                            <span className="text-sm text-gray-600">
                                                {results.count} {results.count === 1 ? 'row' : 'rows'}
                                            </span>
                                        )}
                                        {results.affected_rows !== undefined && (
                                            <span className="text-sm text-gray-600">
                                                {results.affected_rows} {results.affected_rows === 1 ? 'row' : 'rows'} affected
                                            </span>
                                        )}
                                    </div>

                                    {results.data && results.data.length > 0 ? (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        {Object.keys(results.data[0]).map((key) => (
                                                            <th
                                                                key={key}
                                                                className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                            >
                                                                {key}
                                                            </th>
                                                        ))}
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {results.data.map((row, rowIndex) => (
                                                        <tr key={rowIndex} className="hover:bg-gray-50">
                                                            {Object.keys(results.data[0]).map((key) => (
                                                                <td
                                                                    key={key}
                                                                    className="px-4 py-2 whitespace-nowrap text-gray-900"
                                                                >
                                                                    {row[key] !== null && row[key] !== undefined
                                                                        ? String(row[key])
                                                                        : 'NULL'}
                                                                </td>
                                                            ))}
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <div className="text-center py-8 text-gray-500">
                                            No results returned
                                        </div>
                                    )}

                                    {results.executed_query && (
                                        <details className="mt-4">
                                            <summary className="text-sm text-gray-600 cursor-pointer hover:text-gray-900">
                                                View Executed Query (with tenant isolation)
                                            </summary>
                                            <pre className="mt-2 p-3 bg-gray-50 rounded text-xs font-mono text-gray-700 overflow-x-auto">
                                                {results.executed_query}
                                            </pre>
                                        </details>
                                    )}
                                </div>
                            )}

                            {/* Error Display */}
                            {error && (
                                <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div className="flex items-start gap-3">
                                        <XCircle className="h-5 w-5 text-red-600 mt-0.5" />
                                        <div className="flex-1">
                                            <h3 className="font-semibold text-red-900 mb-1">Error</h3>
                                            <p className="text-sm text-red-800">{error}</p>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-4">
                            {/* Table Structures */}
                            {showTables && (
                                <div className="bg-white rounded-lg border border-gray-200 p-4">
                                    <h3 className="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                        <Table className="h-4 w-4" />
                                        Available Tables
                                    </h3>
                                    <div className="space-y-2 max-h-96 overflow-y-auto">
                                        {Object.keys(tableStructures || {}).map((tableName) => (
                                            <div key={tableName} className="border border-gray-200 rounded p-2">
                                                <button
                                                    onClick={() => insertTableName(tableName)}
                                                    className="text-sm font-medium text-teal-600 hover:text-teal-700 mb-2"
                                                >
                                                    {tableName}
                                                </button>
                                                <div className="text-xs text-gray-600 space-y-1">
                                                    {tableStructures[tableName]?.slice(0, 5).map((col, idx) => (
                                                        <div key={idx} className="truncate">
                                                            {col.Field} ({col.Type})
                                                        </div>
                                                    ))}
                                                    {tableStructures[tableName]?.length > 5 && (
                                                        <div className="text-gray-400">
                                                            +{tableStructures[tableName].length - 5} more columns
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Info Card */}
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h3 className="text-sm font-semibold text-blue-900 mb-2">Tenant Isolation</h3>
                                <p className="text-xs text-blue-800 mb-3">
                                    All queries are automatically scoped to your organization. The system automatically adds 
                                    <code className="bg-blue-100 px-1 rounded">organization_id</code> filters to ensure data isolation.
                                </p>
                                <div className="text-xs text-blue-700">
                                    <div className="font-medium mb-1">Organization ID:</div>
                                    <code className="bg-blue-100 px-2 py-1 rounded block break-all">
                                        {organizationId || 'Not set'}
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

