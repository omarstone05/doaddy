import React, { useState } from 'react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { TrendingUp } from 'lucide-react';

const RevenueChartCard = ({ data }) => {
  const [period, setPeriod] = useState('30d');
  
  const chartData = data?.data || [];

  const formatCurrency = (value) => {
    if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
    if (value >= 1000) return `${(value / 1000).toFixed(0)}K`;
    return value.toFixed(0);
  };

  const average = chartData.length > 0 
    ? chartData.reduce((acc, d) => acc + d.revenue, 0) / chartData.length 
    : 0;
  const highest = chartData.length > 0 
    ? Math.max(...chartData.map(d => d.revenue)) 
    : 0;
  const lowest = chartData.length > 0 
    ? Math.min(...chartData.map(d => d.revenue)) 
    : 0;

  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:shadow-lg hover:border-teal-200 transition-all duration-300">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-bold text-gray-900">Revenue Overview</h3>
          <p className="text-sm text-gray-500 mt-0.5">Last 30 days performance</p>
        </div>

        {/* Period Selector */}
        <div className="flex gap-1 bg-gray-100 p-1 rounded-xl">
          {['7d', '30d', '90d'].map((p) => (
            <button
              key={p}
              onClick={() => setPeriod(p)}
              className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                period === p
                  ? 'bg-teal-500 text-white shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              {p.toUpperCase()}
            </button>
          ))}
        </div>
      </div>

      {/* Chart */}
      <div className="h-64">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={chartData}>
            <defs>
              <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#14b8a6" stopOpacity={0.3}/>
                <stop offset="95%" stopColor="#14b8a6" stopOpacity={0}/>
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" vertical={false} />
            <XAxis 
              dataKey="date" 
              stroke="#9ca3af"
              fontSize={11}
              tickLine={false}
              axisLine={false}
              tickFormatter={(date) => new Date(date).toLocaleDateString('en-ZM', { month: 'short', day: 'numeric' })}
            />
            <YAxis 
              stroke="#9ca3af"
              fontSize={11}
              tickLine={false}
              axisLine={false}
              tickFormatter={(value) => `${formatCurrency(value)}`}
            />
            <Tooltip 
              contentStyle={{
                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                border: '1px solid #e7e5e4',
                borderRadius: '12px',
                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
              }}
              formatter={(value) => [`K ${formatCurrency(value)}`, 'Revenue']}
              labelFormatter={(date) => new Date(date).toLocaleDateString('en-ZM', { month: 'short', day: 'numeric' })}
            />
            <Area
              type="monotone"
              dataKey="revenue"
              stroke="#14b8a6"
              strokeWidth={2}
              fill="url(#colorRevenue)"
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>

      {/* Summary Stats */}
      <div className="grid grid-cols-3 gap-3 mt-4 pt-4 border-t border-gray-200/50">
        <div className="p-3 bg-gray-50 rounded-xl text-center">
          <p className="text-xs text-gray-500 mb-1">Average</p>
          <p className="text-lg font-bold text-gray-900">K {formatCurrency(average)}</p>
        </div>
        <div className="p-3 bg-teal-50 rounded-xl text-center">
          <p className="text-xs text-gray-500 mb-1">Highest</p>
          <p className="text-lg font-bold text-teal-600">K {formatCurrency(highest)}</p>
        </div>
        <div className="p-3 bg-gray-50 rounded-xl text-center">
          <p className="text-xs text-gray-500 mb-1">Lowest</p>
          <p className="text-lg font-bold text-gray-900">K {formatCurrency(lowest)}</p>
        </div>
      </div>
    </div>
  );
};

export default RevenueChartCard;
