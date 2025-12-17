import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { useAddy } from '../../Contexts/AddyContext';
import StackedCards from '@/Components/StackedCards';
import { Star, Lightbulb, AlertTriangle, TrendingUp, CheckCircle, XCircle, Sparkles, ArrowRight, MessageCircle } from 'lucide-react';

export function SectionInsightCard({ sectionName, insights = [], sectionIcon }) {
  const addyContext = useAddy();
  const [localInsights, setLocalInsights] = useState(insights);
  
  const { openAddy, dismissInsight } = addyContext || {};

  const handleDismiss = async (insight, direction) => {
    // Right swipe = Take action / Acknowledge
    // Left swipe = Dismiss / Skip
    
    if (direction === 'right' && insight.action_url) {
      router.visit(insight.action_url);
    }
    
    // Remove from local state
    setLocalInsights(prev => prev.filter(i => i.id !== insight.id));
    
    // Call the actual dismiss function
    if (dismissInsight && insight.id) {
      try {
        await dismissInsight(insight.id);
      } catch (error) {
        console.error('Failed to dismiss insight:', error);
      }
    }
  };

  const getTypeConfig = (type) => {
    const configs = {
      alert: {
        icon: AlertTriangle,
        gradient: 'from-red-500 to-rose-600',
        badge: 'bg-red-100 text-red-700',
      },
      suggestion: {
        icon: Lightbulb,
        gradient: 'from-teal-500 to-teal-600',
        badge: 'bg-teal-100 text-teal-700',
      },
      observation: {
        icon: TrendingUp,
        gradient: 'from-emerald-500 to-green-600',
        badge: 'bg-green-100 text-green-700',
      },
      achievement: {
        icon: Star,
        gradient: 'from-amber-500 to-yellow-500',
        badge: 'bg-amber-100 text-amber-700',
      },
      tip: {
        icon: Sparkles,
        gradient: 'from-purple-500 to-indigo-600',
        badge: 'bg-purple-100 text-purple-700',
      },
    };
    return configs[type] || configs.suggestion;
  };

  const renderInsightCard = (insight) => {
    const config = getTypeConfig(insight.type);
    const priorityPercent = Math.round((insight.priority || 0.5) * 100);

    return (
      <div className="w-full h-full bg-white flex flex-col">
        {/* Header */}
        <div className={`bg-gradient-to-r ${config.gradient} p-5`}>
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                <img 
                  src="/assets/logos/icon-white.png" 
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
                {insight.action_url || insight.url ? 'Take Action' : 'Got it!'}
              </span>
              <CheckCircle className="w-4 h-4" />
            </div>
          </div>
        </div>
      </div>
    );
  };

  // Empty state - no insights
  if (!localInsights || localInsights.length === 0) {
    return (
      <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-8 shadow-lg mb-8 relative overflow-hidden border border-gray-200/50">
        <div className="flex items-center gap-6">
          <div className="w-16 h-16 bg-teal-50 rounded-2xl flex items-center justify-center">
            <Sparkles className="w-8 h-8 text-teal-500" />
          </div>
          <div className="flex-1">
            <h2 className="text-2xl font-bold text-teal-700 mb-2">All Clear in {sectionName}! ✨</h2>
            <p className="text-gray-600">
              Everything looks good. Addy is monitoring this area and will alert you if anything needs attention.
            </p>
          </div>
          {openAddy && (
            <button
              onClick={() => openAddy('chat')}
              className="px-4 py-2 bg-teal-50 hover:bg-teal-100 text-teal-600 font-medium rounded-lg transition-colors flex items-center gap-2"
            >
              <MessageCircle className="w-4 h-4" />
              Talk to Addy
            </button>
          )}
        </div>
        
        {/* Background decoration */}
        <div className="absolute -right-4 -bottom-4 opacity-[0.06]">
          <img 
            src="/assets/logos/icon.png" 
            alt="Addy" 
            className="w-32 h-32 transform rotate-12"
          />
        </div>
      </div>
    );
  }

  // Swipeable cards view
  return (
    <div className="mb-8">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
            <img 
              src="/assets/logos/icon-white.png" 
              alt="Addy" 
              className="w-6 h-6 object-contain"
            />
          </div>
          <div>
            <h2 className="text-lg font-bold text-gray-900">{sectionName} Insights</h2>
            <p className="text-gray-500 text-sm">{localInsights.length} insight{localInsights.length !== 1 ? 's' : ''} to review</p>
          </div>
        </div>
        {openAddy && (
          <button
            onClick={() => openAddy('insights')}
            className="px-4 py-2 bg-teal-50 hover:bg-teal-100 text-teal-600 font-medium rounded-lg transition-colors flex items-center gap-2 text-sm"
          >
            <MessageCircle className="w-4 h-4" />
            Open Addy
          </button>
        )}
      </div>

      {/* Swipeable Cards */}
      <div className="flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100/50 rounded-2xl py-8" style={{ minHeight: '520px' }}>
        <StackedCards
          cards={localInsights}
          renderCard={renderInsightCard}
          onDismiss={handleDismiss}
          maxVisible={3}
          cardGap={12}
          scaleStep={0.04}
        />
      </div>

      {/* Instructions */}
      <div className="text-center mt-3">
        <p className="text-gray-400 text-sm">
          ← Swipe left to dismiss • Swipe right to take action →
        </p>
      </div>
    </div>
  );
}
