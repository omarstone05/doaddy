import React from 'react';

export default function IncomeTimelineChart() {
    return (
        <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100 h-full">
            <h3 className="text-lg font-bold text-gray-800 mb-4">Income Forecast</h3>
            <div className="h-64 flex items-end justify-between gap-2">
                {[...Array(12)].map((_, i) => (
                    <div key={i} className="w-full bg-teal-100 rounded-t-lg relative group hover:bg-teal-200 transition-colors">
                        <div 
                            className="absolute bottom-0 w-full bg-teal-500 rounded-t-lg transition-all duration-500"
                            style={{ height: `${Math.random() * 80 + 20}%` }}
                        ></div>
                        <div className="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs text-gray-400">
                            W{i + 1}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
