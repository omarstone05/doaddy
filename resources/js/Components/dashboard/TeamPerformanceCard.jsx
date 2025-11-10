import React from 'react';
import { Card } from '../ui/Card';
import { Users, Award, Target } from 'lucide-react';

export function TeamPerformanceCard({ teamStats }) {
  return (
    <Card className="h-full flex flex-col">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-1">Team Performance</h3>
        <p className="text-sm text-gray-500">This period</p>
      </div>
      
      <div className="flex-1 flex flex-col justify-center">
        <div className="grid grid-cols-3 gap-4 mb-6">
          <div className="text-center">
            <Users className="h-8 w-8 text-teal-500 mx-auto mb-2" />
            <div className="text-2xl font-bold text-gray-900">{teamStats?.totalMembers || 0}</div>
            <div className="text-xs text-gray-500">Team Members</div>
          </div>
          <div className="text-center">
            <Target className="h-8 w-8 text-teal-500 mx-auto mb-2" />
            <div className="text-2xl font-bold text-gray-900">{teamStats?.goalsCompleted || 0}</div>
            <div className="text-xs text-gray-500">Goals Met</div>
          </div>
          <div className="text-center">
            <Award className="h-8 w-8 text-teal-500 mx-auto mb-2" />
            <div className="text-2xl font-bold text-gray-900">{teamStats?.avgPerformance || 0}%</div>
            <div className="text-xs text-gray-500">Avg. Performance</div>
          </div>
        </div>
        
        {teamStats?.topPerformers && teamStats.topPerformers.length > 0 && (
          <div className="space-y-2">
            <p className="text-xs font-medium text-gray-700 mb-2">Top Performers</p>
            {teamStats.topPerformers.slice(0, 3).map((member, index) => (
              <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-full bg-teal-500 flex items-center justify-center text-white text-xs font-semibold">
                    {member.name.charAt(0).toUpperCase()}
                  </div>
                  <span className="text-sm text-gray-900">{member.name}</span>
                </div>
                <span className="text-sm font-semibold text-teal-500">{member.performance}%</span>
              </div>
            ))}
          </div>
        )}
      </div>
    </Card>
  );
}

