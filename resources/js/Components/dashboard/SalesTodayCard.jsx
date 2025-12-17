import React from 'react';
import { Card } from '../ui/Card';
import { Megaphone } from 'lucide-react';

export function SalesTodayCard({ count, link }) {
  return (
    <Card dismissible className="h-full">
      <div className="flex items-start gap-4">
        <div className="p-3 bg-teal-500/10 rounded-xl flex-shrink-0">
          <Megaphone className="h-6 w-6 text-teal-500" />
        </div>
        
        <div className="flex-1">
          <p className="text-sm font-medium text-gray-700 mb-1">
            Sales Today
          </p>
          <p className="text-6xl font-bold text-teal-500 mb-3">
            {count}
          </p>
          <a 
            href={link}
            className="inline-block bg-teal-500 hover:bg-teal-600 text-white font-medium px-6 py-2.5 rounded-lg transition-colors"
          >
            See All Sales
          </a>
        </div>
      </div>
    </Card>
  );
}

