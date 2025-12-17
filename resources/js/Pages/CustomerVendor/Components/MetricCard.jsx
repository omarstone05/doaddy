import React from 'react';
import { ArrowUp, ArrowDown } from 'lucide-react';

export default function MetricCard({ title, value, trend, trendValue, icon: Icon, color = 'teal' }) {
    const isPositive = trend === 'up';
    const trendColor = isPositive ? 'text-green-600' : 'text-red-600';
    const TrendIcon = isPositive ? ArrowUp : ArrowDown;

    const colorClasses = {
        teal: 'bg-teal-50 text-teal-600',
        blue: 'bg-blue-50 text-blue-600',
        purple: 'bg-purple-50 text-purple-600',
        orange: 'bg-orange-50 text-orange-600',
    };

    return (
        <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div className="flex items-center justify-between mb-4">
                <div className={`p-3 rounded-lg ${colorClasses[color]}`}>
                    <Icon className="w-6 h-6" />
                </div>
                {trendValue && (
                    <div className={`flex items-center text-sm font-medium ${trendColor} bg-gray-50 px-2 py-1 rounded-full`}>
                        <TrendIcon className="w-3 h-3 mr-1" />
                        {trendValue}
                    </div>
                )}
            </div>
            <h3 className="text-gray-500 text-sm font-medium">{title}</h3>
            <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
        </div>
    );
}
