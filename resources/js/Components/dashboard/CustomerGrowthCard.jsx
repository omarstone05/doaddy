import React from 'react';
import { Card } from '../ui/Card';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Users, TrendingUp } from 'lucide-react';

export function CustomerGrowthCard({ data, totalCustomers, growthRate }) {
  return (
    <Card className="h-full flex flex-col">
      <div className="mb-4">
        <div className="flex items-center justify-between mb-1">
          <h3 className="text-lg font-semibold text-gray-900">Customer Growth</h3>
          {growthRate !== undefined && (
            <div className="flex items-center gap-1 text-green-600">
              <TrendingUp className="h-4 w-4" />
              <span className="text-sm font-medium">{growthRate > 0 ? '+' : ''}{growthRate}%</span>
            </div>
          )}
        </div>
        <p className="text-sm text-gray-500">New customers over time</p>
      </div>
      
      <div className="flex-1 min-h-[200px]">
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={data}>
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
            <Line 
              type="monotone" 
              dataKey="value" 
              stroke="#00635D"
              strokeWidth={3}
              dot={{ fill: '#00635D', r: 4 }}
              activeDot={{ r: 6 }}
            />
          </LineChart>
        </ResponsiveContainer>
      </div>
      
      <div className="mt-4 pt-4 border-t border-gray-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Users className="h-5 w-5 text-teal-500" />
            <span className="text-sm text-gray-600">Total Customers</span>
          </div>
          <span className="text-lg font-bold text-teal-500">{totalCustomers}</span>
        </div>
      </div>
    </Card>
  );
}

