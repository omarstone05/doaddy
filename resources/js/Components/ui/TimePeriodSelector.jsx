import React, { useState } from 'react';
import { Calendar } from 'lucide-react';

export function TimePeriodSelector({ selected, onChange, dateFrom, dateTo, onCustomDateChange }) {
    const [showCustom, setShowCustom] = useState(selected === 'custom');
    
    const periods = [
        { value: 'week', label: 'Week' },
        { value: 'month', label: 'Month' },
        { value: 'year', label: 'Year' },
        { value: 'custom', label: 'Custom' },
    ];

    const handleChange = (period) => {
        if (period === 'custom') {
            setShowCustom(true);
        } else {
            setShowCustom(false);
        }
        onChange(period);
    };

    return (
        <div className="flex items-center gap-4">
            <div className="inline-flex items-center bg-gray-100/80 rounded-xl p-1 backdrop-blur-sm">
                {periods.map((period) => (
                    <button
                        key={period.value}
                        onClick={() => handleChange(period.value)}
                        className={`px-4 py-1.5 text-sm font-semibold rounded-lg transition-all duration-200 ${
                            selected === period.value
                                ? 'bg-white text-teal-600 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
                        }`}
                    >
                        {period.label}
                    </button>
                ))}
            </div>
            
            {showCustom && (
                <div className="flex items-center gap-2">
                    <div className="flex items-center gap-2 bg-white/90 backdrop-blur-sm border border-gray-200 rounded-xl px-3 py-1.5">
                        <Calendar className="w-4 h-4 text-gray-400" />
                        <input
                            type="date"
                            value={dateFrom}
                            onChange={(e) => onCustomDateChange('date_from', e.target.value)}
                            className="text-sm font-medium bg-transparent border-0 focus:outline-none focus:ring-0 w-32"
                        />
                        <span className="text-gray-400">→</span>
                        <input
                            type="date"
                            value={dateTo}
                            onChange={(e) => onCustomDateChange('date_to', e.target.value)}
                            className="text-sm font-medium bg-transparent border-0 focus:outline-none focus:ring-0 w-32"
                        />
                    </div>
                </div>
            )}
        </div>
    );
}

export default TimePeriodSelector;

