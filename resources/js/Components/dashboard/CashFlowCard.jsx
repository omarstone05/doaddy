import React from 'react';
import { Card } from '../ui/Card';
import { TrendingUp, TrendingDown, ArrowRight } from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

export function CashFlowCard({ data, totalInflow, totalOutflow, netFlow }) {
  const isPositive = netFlow >= 0;
  
  return (
    <Card className="h-full flex flex-col">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-1">Cash Flow</h3>
        <p className="text-sm text-gray-500">Inflow vs Outflow</p>
      </div>
      
      <div className="flex-1 min-h-[200px]">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={data}>
            <defs>
              <linearGradient id="inflowGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#7DCD85" stopOpacity={0.3}/>
                <stop offset="95%" stopColor="#7DCD85" stopOpacity={0}/>
              </linearGradient>
              <linearGradient id="outflowGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#EF4444" stopOpacity={0.3}/>
                <stop offset="95%" stopColor="#EF4444" stopOpacity={0}/>
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
              }}
            />
            <Area 
              type="monotone" 
              dataKey="inflow" 
              stroke="#7DCD85"
              strokeWidth={2}
              fill="url(#inflowGradient)" 
            />
            <Area 
              type="monotone" 
              dataKey="outflow" 
              stroke="#EF4444"
              strokeWidth={2}
              fill="url(#outflowGradient)" 
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>
      
      <div className="mt-4 pt-4 border-t border-gray-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="w-3 h-3 rounded-full bg-green-500"></div>
            <span className="text-sm text-gray-600">Inflow</span>
            <span className="text-sm font-semibold text-gray-900">{totalInflow}</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-3 h-3 rounded-full bg-red-500"></div>
            <span className="text-sm text-gray-600">Outflow</span>
            <span className="text-sm font-semibold text-gray-900">{totalOutflow}</span>
          </div>
          <div className={`flex items-center gap-1 ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
            {isPositive ? <TrendingUp className="h-4 w-4" /> : <TrendingDown className="h-4 w-4" />}
            <span className="text-sm font-semibold">{netFlow}</span>
          </div>
        </div>
      </div>
    </Card>
  );
}

