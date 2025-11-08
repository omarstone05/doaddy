import React from 'react';
import { BackgroundGradientAnimation } from '../ui/BackgroundGradientAnimation';
import { router } from '@inertiajs/react';

export function DecisionsInsightCard({ sectionName, insights }) {
  // Get the top insight for this section
  const topInsight = insights && insights.length > 0 
    ? insights[0] 
    : null;

  const getSectionMessage = () => {
    if (!topInsight) {
      return `Everything looks good in ${sectionName}. I'm monitoring this area and will alert you if anything needs attention.`;
    }

    return topInsight.description || `I have insights for your ${sectionName} section.`;
  };

  const handleActionClick = () => {
    if (topInsight?.action_url) {
      router.visit(topInsight.action_url);
    }
  };

  return (
    <div className="relative h-full w-full overflow-hidden rounded-3xl bg-white border border-mint-200 shadow-lg mb-8">
      {/* Animated background elements - mint gradient */}
      <div className="absolute inset-0 overflow-hidden">
        {/* Animated blob 1 */}
        <div className="absolute top-0 left-0 w-72 h-72 bg-mint-300/40 rounded-full blur-3xl animate-move-vertical" />
        
        {/* Animated blob 2 */}
        <div className="absolute top-1/4 right-0 w-96 h-96 bg-mint-400/35 rounded-full blur-3xl animate-move-circle-reverse" />
        
        {/* Animated blob 3 */}
        <div className="absolute bottom-0 left-1/3 w-80 h-80 bg-mint-200/40 rounded-full blur-3xl animate-move-circle-slow" />
        
        {/* Animated blob 4 */}
        <div className="absolute top-1/2 right-1/4 w-64 h-64 bg-mint-300/30 rounded-full blur-3xl animate-move-horizontal" />
        
        {/* Animated blob 5 */}
        <div className="absolute bottom-1/4 left-1/2 w-56 h-56 bg-mint-400/25 rounded-full blur-3xl animate-move-circle" />
      </div>

      {/* Content */}
      <div className="relative z-10 p-8">
        <div className="flex justify-between items-start">
          <div className="max-w-md">
            <p className="text-sm font-medium text-teal-600 mb-3">
              {sectionName} Insights
            </p>
            <h2 className="text-3xl font-bold text-teal-700 mb-3">
              {topInsight ? topInsight.title : `Your ${sectionName} Overview`}
            </h2>
            <p className="text-teal-600 leading-relaxed mb-4">
              {getSectionMessage()}
            </p>
            
            {topInsight && topInsight.is_actionable && topInsight.action_url && (
              <button
                onClick={handleActionClick}
                className="px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white font-medium rounded-lg transition-colors"
              >
                Take Action →
              </button>
            )}

            {insights && insights.length > 1 && (
              <p className="text-teal-500 text-sm mt-4">
                +{insights.length - 1} more insight{insights.length - 1 !== 1 ? 's' : ''}
              </p>
            )}
          </div>
        </div>
        
        {/* Large Teal Addy Icon */}
        <div className="absolute -right-4 -bottom-4 opacity-10 z-20">
          <img 
            src="/assets/logos/icon-white.png" 
            alt="Addy" 
            className="w-48 h-48 transform rotate-12"
            style={{ filter: 'brightness(0) saturate(100%) invert(27%) sepia(51%) saturate(2878%) hue-rotate(142deg) brightness(95%) contrast(101%)' }}
          />
        </div>
      </div>
    </div>
  );
}

