import React, { useState, useEffect } from 'react';
import { BackgroundGradientAnimation } from '../ui/BackgroundGradientAnimation';
import { useAddy } from '../../Contexts/AddyContext';
import { router, usePage } from '@inertiajs/react';
import { getOrganizationTheme } from '../../utils/themeColors';

export function InsightsCard({ userName = 'User', organizationName, message }) {
  const { props } = usePage();
  const themeIndex = props?.auth?.user?.organization?.theme_index ?? 0;
  const theme = getOrganizationTheme(themeIndex);
  const addyContext = useAddy();
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isTransitioning, setIsTransitioning] = useState(false);
  
  // If context is not available, render fallback
  if (!addyContext) {
    return (
      <div className={`bg-gradient-to-br ${theme.gradient} p-6 md:p-8 shadow-lg rounded-2xl h-full flex items-center justify-center`}>
        <div className="text-white text-center">
          <p className="text-lg font-semibold mb-2">Insights</p>
          <p className="text-sm opacity-80">Loading insights...</p>
        </div>
      </div>
    );
  }
  
  const { openAddy, topInsight, insights, state, hasInsights } = addyContext;

  // Prepare insights array - include topInsight if it exists and isn't already in insights
  const allInsights = React.useMemo(() => {
    if (!insights || insights.length === 0) {
      return topInsight ? [topInsight] : [];
    }
    // Check if topInsight is already in insights
    const topInList = insights.some(insight => insight.id === topInsight?.id);
    if (topInsight && !topInList) {
      return [topInsight, ...insights];
    }
    return insights;
  }, [insights, topInsight]);

  // Cycle through insights every 5 seconds
  useEffect(() => {
    if (allInsights.length <= 1) return;

    const interval = setInterval(() => {
      setIsTransitioning(true);
      setTimeout(() => {
        setCurrentIndex((prev) => (prev + 1) % allInsights.length);
        setIsTransitioning(false);
      }, 250); // Half of transition duration (500ms / 2)
    }, 5000); // Change every 5 seconds

    return () => clearInterval(interval);
  }, [allInsights.length]);

  // Get current insight to display
  const currentInsight = allInsights.length > 0 ? allInsights[currentIndex] : null;

  // Use current insight if available, otherwise use the passed message
  const displayMessage = currentInsight 
    ? currentInsight.description 
    : (state?.context || message);

  const displayTitle = currentInsight 
    ? currentInsight.title 
    : `Hi, ${userName}`;

  const handleCardClick = () => {
    if (openAddy) {
      openAddy(currentInsight || topInsight ? 'insights' : 'chat');
    }
  };

  return (
    <BackgroundGradientAnimation className={`bg-gradient-to-br ${theme.gradient} p-6 md:p-8 shadow-lg cursor-pointer hover:shadow-xl transition-shadow h-full relative overflow-hidden flex flex-col`} onClick={handleCardClick}>
      {/* Content Section */}
      <div className="flex-1 flex flex-col relative z-10">
        {/* Header Label */}
        <p className="text-xs md:text-sm font-medium text-white/80 mb-2 md:mb-3">
          {hasInsights ? 'Active Insights' : 'Insights'}
        </p>
        
        {/* Business Name */}
        {organizationName && (
          <p className="text-xs md:text-sm font-medium text-white/70 mb-1 md:mb-2">
            {organizationName}
          </p>
        )}
        
        {/* Title with smooth transition */}
        <div className="relative mb-3 md:mb-4 overflow-hidden">
          <h2 
            className={`text-xl md:text-2xl font-bold text-white transition-opacity duration-500 ease-in-out leading-tight ${
              isTransitioning ? 'opacity-0' : 'opacity-100'
            }`}
            style={{
              display: '-webkit-box',
              WebkitLineClamp: 2,
              WebkitBoxOrient: 'vertical',
              overflow: 'hidden',
              textOverflow: 'ellipsis'
            }}
          >
            {displayTitle}
          </h2>
        </div>
        
        {/* Message with smooth transition */}
        <div className="relative flex-1 mb-4 md:mb-6 overflow-hidden min-h-[3rem] md:min-h-[4rem]">
          <p 
            className={`text-sm md:text-base text-white/90 leading-relaxed transition-opacity duration-500 ease-in-out ${
              isTransitioning ? 'opacity-0' : 'opacity-100'
            }`}
          >
            {displayMessage}
          </p>
        </div>
        
        {/* Action buttons */}
        <div className={`transition-opacity duration-500 ease-in-out mb-4 ${
          isTransitioning ? 'opacity-0' : 'opacity-100'
        }`}>
          {currentInsight && currentInsight.url && (
            <div className="flex gap-2 md:gap-3 flex-wrap">
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  if (currentInsight.url) {
                    router.visit(currentInsight.url);
                  }
                }}
                className="px-3 md:px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm md:text-base font-medium rounded-lg transition-colors backdrop-blur-sm"
              >
                Take Action →
              </button>
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  if (openAddy) {
                    openAddy('insights');
                  }
                }}
                className="px-3 md:px-4 py-2 bg-white/20 hover:bg-white/40 text-white text-sm md:text-base font-medium rounded-lg transition-colors backdrop-blur-sm"
              >
                View Insights
              </button>
            </div>
          )}

            {!currentInsight && (
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  if (openAddy) {
                    openAddy('chat');
                  }
                }}
                className="px-3 md:px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm md:text-base font-medium rounded-lg transition-colors backdrop-blur-sm"
              >
                Talk to Addy →
              </button>
            )}
        </div>

        {/* Pagination indicator - positioned at bottom */}
        {hasInsights && allInsights.length > 1 && (
          <div className="flex items-center gap-2 md:gap-3 mt-auto pt-2">
            <p className="text-white/70 text-xs md:text-sm">
              {currentIndex + 1} of {allInsights.length}
            </p>
            {/* Dots indicator */}
            <div className="flex gap-1 md:gap-1.5">
              {allInsights.map((_, index) => (
                <div
                  key={index}
                  className={`h-1.5 md:h-2 rounded-full transition-all duration-300 ${
                    index === currentIndex 
                      ? 'bg-white w-5 md:w-6' 
                      : 'bg-white/40 w-1.5 md:w-2'
                  }`}
                />
              ))}
            </div>
          </div>
        )}

        {hasInsights && allInsights.length === 1 && (
          <p className="text-white/70 text-xs md:text-sm mt-auto pt-2">
            Click to view all insights
          </p>
        )}
      </div>
      
      {/* Proportional Addy Icon - positioned in bottom right */}
      <div className="absolute right-0 bottom-0 opacity-15 md:opacity-20 z-0 pointer-events-none">
        <img 
          src="/assets/logos/icon-white.png" 
          alt="Addy" 
          className="w-24 h-24 md:w-32 md:h-32 lg:w-40 lg:h-40 transform rotate-12"
        />
      </div>
    </BackgroundGradientAnimation>
  );
}
