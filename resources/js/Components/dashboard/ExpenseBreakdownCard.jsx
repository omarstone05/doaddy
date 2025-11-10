import React from 'react';
import { Card } from '../ui/Card';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';

export function ExpenseBreakdownCard({ data }) {
  return (
    <Card className="h-full flex flex-col">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-1">Expense Breakdown</h3>
        <p className="text-sm text-gray-500">By category</p>
      </div>
      
      <div className="flex-1 min-h-[250px]">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={data}>
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
            <Bar dataKey="amount" fill="#00635D" radius={[8, 8, 0, 0]} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </Card>
  );
}

