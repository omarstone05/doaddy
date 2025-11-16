import React, { useState, useEffect } from 'react';
import { BackgroundGradientAnimation } from '../ui/BackgroundGradientAnimation';
import { useAddy } from '../../Contexts/AddyContext';
import { router } from '@inertiajs/react';

export function InsightsCard({ userName, message }) {
  const { openAddy, topInsight, insights, state, hasInsights } = useAddy();
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isTransitioning, setIsTransitioning] = useState(false);

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
      }, 300); // Half of transition duration
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
    openAddy(currentInsight || topInsight ? 'insights' : 'chat');
  };

  return (
    <BackgroundGradientAnimation className="bg-gradient-to-br from-teal-500 to-mint-300 p-8 shadow-lg cursor-pointer hover:shadow-xl transition-shadow h-full relative overflow-hidden" onClick={handleCardClick}>
      <div className="flex justify-between items-start">
        <div className="max-w-md relative">
          <p className="text-sm font-medium text-white/80 mb-3">
            {hasInsights ? 'Active Insights' : 'Insights'}
          </p>
          
          {/* Title with smooth transition */}
          <div className="relative h-12 mb-3 overflow-hidden">
            <h2 
              className={`text-3xl font-bold text-white absolute inset-0 transition-opacity duration-600 ease-in-out ${
                isTransitioning ? 'opacity-0' : 'opacity-100'
              }`}
            >
              {displayTitle}
            </h2>
          </div>
          
          {/* Message with smooth transition */}
          <div className="relative min-h-[4rem] mb-4 overflow-hidden">
            <p 
              className={`text-white/90 leading-relaxed absolute inset-0 transition-opacity duration-600 ease-in-out ${
                isTransitioning ? 'opacity-0' : 'opacity-100'
              }`}
            >
              {displayMessage}
            </p>
          </div>
          
          {/* Action buttons */}
          <div className={`transition-opacity duration-600 ease-in-out ${
            isTransitioning ? 'opacity-0' : 'opacity-100'
          }`}>
            {currentInsight && currentInsight.url && (
              <div className="flex gap-3 flex-wrap">
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    if (currentInsight.url) {
                      router.visit(currentInsight.url);
                    }
                  }}
                  className="px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors backdrop-blur-sm"
                >
                  Take Action →
                </button>
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    openAddy('insights');
                  }}
                  className="px-4 py-2 bg-white/20 hover:bg-white/40 text-white font-medium rounded-lg transition-colors backdrop-blur-sm"
                >
                  View Insights
                </button>
              </div>
            )}

            {!currentInsight && (
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  openAddy('chat');
                }}
                className="px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors backdrop-blur-sm"
              >
                Talk to Addy →
              </button>
            )}

            {hasInsights && allInsights.length > 1 && (
              <div className="flex items-center gap-3 mt-4">
                <p className="text-white/70 text-sm">
                  {currentIndex + 1} of {allInsights.length}
                </p>
                {/* Dots indicator */}
                <div className="flex gap-1.5">
                  {allInsights.map((_, index) => (
                    <div
                      key={index}
                      className={`h-1.5 rounded-full transition-all duration-300 ${
                        index === currentIndex 
                          ? 'bg-white w-6' 
                          : 'bg-white/40 w-1.5'
                      }`}
                    />
                  ))}
                </div>
              </div>
            )}

            {hasInsights && allInsights.length === 1 && (
              <p className="text-white/70 text-sm mt-4">
                Click to view all insights
              </p>
            )}
          </div>
        </div>
      </div>
      
      {/* Large White Addy Icon - positioned relative to BackgroundGradientAnimation */}
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
