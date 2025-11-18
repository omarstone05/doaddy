// Revenue Chart Card with glassmorphism
import React, { useState } from 'react';
import { LineChart, Line, AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { TrendingUp, Calendar } from 'lucide-react';

const RevenueChartCard = ({ data }) => {
  const [period, setPeriod] = useState('30d');
  
  const chartData = data?.data || [];

  // Custom tooltip with glassmorphism
  const CustomTooltip = ({ active, payload }) => {
    if (!active || !payload?.length) return null;

    return (
      <div className="glass-strong px-3 py-2 border-none shadow-lg">
        <p className="text-xs text-gray-600 mb-1">
          {new Date(payload[0].payload.date).toLocaleDateString('en-ZM', {
            month: 'short',
            day: 'numeric'
          })}
        </p>
        <p className="text-sm font-semibold text-gray-900">
          ZMW {payload[0].value.toLocaleString()}
        </p>
      </div>
    );
  };

  return (
    <div className="h-full">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <TrendingUp size={20} className="text-green-500" />
            Revenue Overview
          </h3>
          <p className="text-sm text-gray-600 mt-1">Last 30 days performance</p>
        </div>

        {/* Period Selector */}
        <div className="flex gap-2">
          {['7d', '30d', '90d'].map((p) => (
            <button
              key={p}
              onClick={() => setPeriod(p)}
              className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                period === p
                  ? 'bg-teal-500 text-white shadow-md'
                  : 'bg-white/50 text-gray-600 hover:bg-white/80'
              }`}
            >
              {p.toUpperCase()}
            </button>
          ))}
        </div>
      </div>

      {/* Chart */}
      <div className="h-80">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={chartData}>
            <defs>
              <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#7DCD85" stopOpacity={0.3}/>
                <stop offset="95%" stopColor="#7DCD85" stopOpacity={0}/>
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" stroke="#E5E5E5" vertical={false} />
            <XAxis 
              dataKey="date" 
              stroke="#A3A3A3"
              fontSize={12}
              tickFormatter={(date) => new Date(date).toLocaleDateString('en-ZM', { month: 'short', day: 'numeric' })}
            />
            <YAxis 
              stroke="#A3A3A3"
              fontSize={12}
              tickFormatter={(value) => `${(value / 1000).toFixed(0)}K`}
            />
            <Tooltip content={<CustomTooltip />} />
            <Area
              type="monotone"
              dataKey="revenue"
              stroke="#7DCD85"
              strokeWidth={3}
              fill="url(#colorRevenue)"
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>

      {/* Summary Stats */}
      <div className="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-200">
        <div>
          <div className="text-xs text-gray-500 mb-1">Average</div>
          <div className="text-lg font-semibold text-gray-900">
            ZMW {(chartData.reduce((acc, d) => acc + d.revenue, 0) / chartData.length || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}
          </div>
        </div>
        <div>
          <div className="text-xs text-gray-500 mb-1">Highest</div>
          <div className="text-lg font-semibold text-gray-900">
            ZMW {Math.max(...chartData.map(d => d.revenue), 0).toLocaleString()}
          </div>
        </div>
        <div>
          <div className="text-xs text-gray-500 mb-1">Lowest</div>
          <div className="text-lg font-semibold text-gray-900">
            ZMW {Math.min(...chartData.map(d => d.revenue), 999999).toLocaleString()}
          </div>
        </div>
      </div>
    </div>
  );
};

export default RevenueChartCard;

