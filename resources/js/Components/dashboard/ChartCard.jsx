import React from 'react';
import { Card } from '../ui/Card';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

export function ChartCard({ 
  title, 
  value, 
  subtitle, 
  data,
  dataKey = 'value',
  color = '#7DCD85',
  dismissible = true 
}) {
  return (
    <Card dismissible={dismissible} className="h-full flex flex-col">
      <div className="mb-6">
        <h3 className="text-sm font-medium text-gray-700 mb-2">
          {title}
        </h3>
        {value && (
          <p className="text-4xl font-bold text-teal-500">
            {value}
          </p>
        )}
        {subtitle && (
          <p className="text-sm text-gray-500 mt-1">
            {subtitle}
          </p>
        )}
      </div>
      
      <div className="flex-1 min-h-[200px]">
        <ResponsiveContainer width="100%" height="100%">
        <AreaChart data={data}>
          <defs>
            <linearGradient id={`color${dataKey}`} x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={color} stopOpacity={0.3}/>
              <stop offset="95%" stopColor={color} stopOpacity={0}/>
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" stroke="#F5F5F5" />
          <XAxis 
            dataKey="name" 
            tick={{ fontSize: 12, fill: '#A3A3A3' }}
            axisLine={false}
          />
          <YAxis 
            tick={{ fontSize: 12, fill: '#A3A3A3' }}
            axisLine={false}
          />
          <Tooltip 
            contentStyle={{
              backgroundColor: 'white',
              border: '1px solid #E5E5E5',
              borderRadius: '8px',
              boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
            }}
          />
          <Area 
            type="monotone" 
            dataKey={dataKey} 
            stroke={color}
            strokeWidth={2}
            fillOpacity={1} 
            fill={`url(#color${dataKey})`} 
          />
        </AreaChart>
      </ResponsiveContainer>
      </div>
    </Card>
  );
}

