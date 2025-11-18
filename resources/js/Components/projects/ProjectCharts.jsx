import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/Card';
import {
    PieChart,
    Pie,
    Cell,
    BarChart,
    Bar,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';

const COLORS = {
    status: {
        planning: '#94a3b8',
        active: '#10b981',
        on_hold: '#f59e0b',
        completed: '#3b82f6',
        cancelled: '#ef4444',
    },
    priority: {
        low: '#94a3b8',
        medium: '#3b82f6',
        high: '#f59e0b',
        urgent: '#ef4444',
    },
    task: {
        todo: '#94a3b8',
        in_progress: '#3b82f6',
        done: '#10b981',
    },
};

/**
 * Project Status Distribution Chart (Pie Chart)
 */
export function ProjectStatusChart({ data }) {
    if (!data || data.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Project Status Distribution</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center justify-center h-64 text-gray-500">
                        No data available
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Project Status Distribution</CardTitle>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                        <Pie
                            data={data}
                            cx="50%"
                            cy="50%"
                            labelLine={false}
                            label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                            outerRadius={100}
                            fill="#8884d8"
                            dataKey="value"
                        >
                            {data.map((entry, index) => (
                                <Cell
                                    key={`cell-${index}`}
                                    fill={COLORS.status[entry.name.toLowerCase().replace(' ', '_')] || '#94a3b8'}
                                />
                            ))}
                        </Pie>
                        <Tooltip />
                        <Legend />
                    </PieChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}

/**
 * Task Status Breakdown Chart (Bar Chart)
 */
export function TaskStatusChart({ data }) {
    if (!data || data.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Task Status Breakdown</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center justify-center h-64 text-gray-500">
                        No data available
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Task Status Breakdown</CardTitle>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                    <BarChart data={data}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                        <XAxis
                            dataKey="name"
                            tick={{ fontSize: 12, fill: '#6b7280' }}
                            axisLine={false}
                        />
                        <YAxis
                            tick={{ fontSize: 12, fill: '#6b7280' }}
                            axisLine={false}
                        />
                        <Tooltip
                            contentStyle={{
                                backgroundColor: 'white',
                                border: '1px solid #e5e7eb',
                                borderRadius: '8px',
                                boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
                            }}
                        />
                        <Bar
                            dataKey="value"
                            radius={[8, 8, 0, 0]}
                        >
                            {data.map((entry, index) => (
                                <Cell
                                    key={`cell-${index}`}
                                    fill={COLORS.task[entry.name.toLowerCase().replace(' ', '_')] || '#3b82f6'}
                                />
                            ))}
                        </Bar>
                    </BarChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}

/**
 * Budget Utilization Chart (Donut Chart)
 */
export function BudgetUtilizationChart({ totalBudget, totalSpent }) {
    const remaining = Math.max(0, totalBudget - totalSpent);
    const utilization = totalBudget > 0 ? (totalSpent / totalBudget) * 100 : 0;

    const data = [
        { name: 'Spent', value: totalSpent },
        { name: 'Remaining', value: remaining },
    ];

    return (
        <Card>
            <CardHeader>
                <CardTitle>Budget Utilization</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex flex-col items-center">
                    <ResponsiveContainer width="100%" height={250}>
                        <PieChart>
                            <Pie
                                data={data}
                                cx="50%"
                                cy="50%"
                                innerRadius={60}
                                outerRadius={100}
                                paddingAngle={5}
                                dataKey="value"
                            >
                                <Cell fill="#ef4444" />
                                <Cell fill="#10b981" />
                            </Pie>
                            <Tooltip
                                formatter={(value) =>
                                    new Intl.NumberFormat('en-ZM', {
                                        style: 'currency',
                                        currency: 'ZMW',
                                        minimumFractionDigits: 0,
                                    }).format(value)
                                }
                            />
                        </PieChart>
                    </ResponsiveContainer>
                    <div className="mt-4 text-center">
                        <div className="text-2xl font-bold text-gray-900">
                            {utilization.toFixed(1)}%
                        </div>
                        <div className="text-sm text-gray-500">Utilized</div>
                        <div className="mt-2 flex items-center justify-center gap-4 text-xs">
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 rounded-full bg-red-500"></div>
                                <span>Spent: {new Intl.NumberFormat('en-ZM', {
                                    style: 'currency',
                                    currency: 'ZMW',
                                    minimumFractionDigits: 0,
                                }).format(totalSpent)}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 rounded-full bg-green-500"></div>
                                <span>Remaining: {new Intl.NumberFormat('en-ZM', {
                                    style: 'currency',
                                    currency: 'ZMW',
                                    minimumFractionDigits: 0,
                                }).format(remaining)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

/**
 * Project Priority Distribution Chart (Pie Chart)
 */
export function ProjectPriorityChart({ data }) {
    if (!data || data.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Project Priority Distribution</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center justify-center h-64 text-gray-500">
                        No data available
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Project Priority Distribution</CardTitle>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                        <Pie
                            data={data}
                            cx="50%"
                            cy="50%"
                            labelLine={false}
                            label={({ name, value }) => `${name}: ${value}`}
                            outerRadius={100}
                            fill="#8884d8"
                            dataKey="value"
                        >
                            {data.map((entry, index) => (
                                <Cell
                                    key={`cell-${index}`}
                                    fill={COLORS.priority[entry.name.toLowerCase()] || '#94a3b8'}
                                />
                            ))}
                        </Pie>
                        <Tooltip />
                        <Legend />
                    </PieChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}

/**
 * Project Progress Over Time (Line Chart)
 */
export function ProjectProgressChart({ data }) {
    if (!data || data.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Project Progress Trend</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center justify-center h-64 text-gray-500">
                        No data available
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Project Progress Trend</CardTitle>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                    <LineChart data={data}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                        <XAxis
                            dataKey="name"
                            tick={{ fontSize: 12, fill: '#6b7280' }}
                            axisLine={false}
                        />
                        <YAxis
                            tick={{ fontSize: 12, fill: '#6b7280' }}
                            axisLine={false}
                            domain={[0, 100]}
                        />
                        <Tooltip
                            contentStyle={{
                                backgroundColor: 'white',
                                border: '1px solid #e5e7eb',
                                borderRadius: '8px',
                                boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
                            }}
                            formatter={(value) => `${value}%`}
                        />
                        <Legend />
                        <Line
                            type="monotone"
                            dataKey="progress"
                            stroke="#3b82f6"
                            strokeWidth={2}
                            name="Average Progress"
                            dot={{ fill: '#3b82f6', r: 4 }}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}

