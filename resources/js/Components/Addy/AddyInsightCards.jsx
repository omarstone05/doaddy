import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import StackedCards from '@/Components/StackedCards';
import { Star, Lightbulb, AlertTriangle, TrendingUp, CheckCircle, XCircle, Sparkles, ArrowRight } from 'lucide-react';

/**
 * AddyInsightCards - Swipeable card stack for Addy AI insights
 * Replaces the old static insight cards with interactive swipeable experience
 */
const AddyInsightCards = ({ insights = [], onDismiss, onAction, sectionName }) => {
  const [dismissedIds, setDismissedIds] = useState([]);

  // Filter out dismissed insights
  const activeInsights = insights.filter(i => !dismissedIds.includes(i.id));

  const handleDismiss = async (insight, direction) => {
    // Right swipe = Take action / Acknowledge
    // Left swipe = Dismiss / Skip
    
    if (direction === 'right' && insight.action_url) {
      // Navigate to action URL
      if (onAction) onAction(insight, 'action');
      router.visit(insight.action_url);
    } else {
      // Dismiss the insight
      setDismissedIds(prev => [...prev, insight.id]);
      
      if (onDismiss) {
        try {
          await onDismiss(insight.id);
        } catch (error) {
          console.error('Failed to dismiss insight:', error);
        }
      }
    }
  };

  const getTypeConfig = (type) => {
    const configs = {
      alert: {
        icon: AlertTriangle,
        gradient: 'from-red-500 to-rose-600',
        badge: 'bg-red-100 text-red-700',
        iconColor: 'text-red-500',
      },
      suggestion: {
        icon: Lightbulb,
        gradient: 'from-teal-500 to-teal-600',
        badge: 'bg-teal-100 text-teal-700',
        iconColor: 'text-teal-500',
      },
      observation: {
        icon: TrendingUp,
        gradient: 'from-emerald-500 to-green-600',
        badge: 'bg-green-100 text-green-700',
        iconColor: 'text-green-500',
      },
      achievement: {
        icon: Star,
        gradient: 'from-amber-500 to-yellow-500',
        badge: 'bg-amber-100 text-amber-700',
        iconColor: 'text-amber-500',
      },
      tip: {
        icon: Sparkles,
        gradient: 'from-purple-500 to-indigo-600',
        badge: 'bg-purple-100 text-purple-700',
        iconColor: 'text-purple-500',
      },
    };
    return configs[type] || configs.suggestion;
  };

  const renderInsightCard = (insight) => {
    const config = getTypeConfig(insight.type);
    const IconComponent = config.icon;
    const priorityPercent = Math.round((insight.priority || 0.5) * 100);

    return (
      <div className="w-full h-full bg-white flex flex-col">
        {/* Header */}
        <div className={`bg-gradient-to-r ${config.gradient} p-5`}>
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                <img 
                  src="/assets/logos/icon-white.webp" 
                  alt="Addy" 
                  className="w-8 h-8 object-contain"
                />
              </div>
              <div>
                <p className="text-white/80 text-xs font-medium">
                  {sectionName ? `${sectionName} Insight` : 'Addy Insight'}
                </p>
                <div className="flex items-center gap-2 mt-1">
                  <span className={`px-2 py-0.5 rounded-full text-xs font-semibold uppercase ${config.badge}`}>
                    {insight.type}
                  </span>
                  <span className="text-white/70 text-xs">
                    {priorityPercent}% priority
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Body */}
        <div className="flex-1 p-6 flex flex-col">
          <h3 className="text-lg font-bold text-gray-900 mb-3">{insight.title}</h3>
          <p className="text-gray-600 leading-relaxed text-sm flex-1">
            {insight.description}
          </p>

          {/* Suggested Actions */}
          {insight.actions && insight.actions.length > 0 && (
            <div className="mt-4 p-3 bg-gray-50 rounded-xl">
              <p className="text-xs font-semibold text-gray-500 mb-2">Suggested:</p>
              <ul className="space-y-1">
                {insight.actions.slice(0, 2).map((action, idx) => (
                  <li key={idx} className="flex items-start gap-2 text-sm text-gray-700">
                    <ArrowRight className="w-3 h-3 text-teal-500 mt-1 flex-shrink-0" />
                    <span>{action}</span>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>

        {/* Footer with swipe hints */}
        <div className="p-4 bg-gray-50 border-t border-gray-100">
          <div className="flex items-center justify-between text-sm">
            <div className="flex items-center gap-2 text-gray-400">
              <XCircle className="w-4 h-4" />
              <span className="font-medium">Dismiss</span>
            </div>
            <div className="flex items-center gap-2 text-teal-500">
              <span className="font-medium">
                {insight.action_url ? 'Take Action' : 'Got it!'}
              </span>
              <CheckCircle className="w-4 h-4" />
            </div>
          </div>
        </div>
      </div>
    );
  };

  if (activeInsights.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center h-full text-center p-8">
        <div className="w-20 h-20 mx-auto mb-4 rounded-2xl bg-teal-100 flex items-center justify-center">
          <Sparkles className="w-10 h-10 text-teal-500" />
        </div>
        <h3 className="text-xl font-bold text-gray-900 mb-2">All Clear! ✨</h3>
        <p className="text-gray-600 max-w-sm">
          Addy is monitoring your business. I'll let you know if anything needs your attention.
        </p>
      </div>
    );
  }

  return (
    <StackedCards
      cards={activeInsights}
      renderCard={renderInsightCard}
      onDismiss={handleDismiss}
      maxVisible={3}
      cardGap={12}
      scaleStep={0.04}
    />
  );
};

export default AddyInsightCards;

