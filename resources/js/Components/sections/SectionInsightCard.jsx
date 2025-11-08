import React from 'react';
import { BackgroundGradientAnimation } from '../ui/BackgroundGradientAnimation';
import { router } from '@inertiajs/react';

export function SectionInsightCard({ sectionName, insights, sectionIcon }) {
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
    <BackgroundGradientAnimation className="bg-gradient-to-br from-teal-500 to-mint-300 p-8 shadow-lg mb-8">
      <div className="flex justify-between items-start">
        <div className="max-w-md">
          <p className="text-sm font-medium text-white/80 mb-3">
            {sectionName} Insights
          </p>
          <h2 className="text-3xl font-bold text-white mb-3">
            {topInsight ? topInsight.title : `Your ${sectionName} Overview`}
          </h2>
          <p className="text-white/90 leading-relaxed mb-4">
            {getSectionMessage()}
          </p>
          
          {topInsight && topInsight.is_actionable && topInsight.action_url && (
            <button
              onClick={handleActionClick}
              className="px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors backdrop-blur-sm"
            >
              Take Action →
            </button>
          )}

          {insights && insights.length > 1 && (
            <p className="text-white/70 text-sm mt-4">
              +{insights.length - 1} more insight{insights.length - 1 !== 1 ? 's' : ''}
            </p>
          )}
        </div>
      </div>
      
      {/* Large White Addy Icon */}
      <div className="absolute -right-4 -bottom-4 opacity-20 z-20">
        <img 
          src="/assets/logos/icon-white.png" 
          alt="Addy" 
          className="w-48 h-48 transform rotate-12"
        />
      </div>
    </BackgroundGradientAnimation>
  );
}

