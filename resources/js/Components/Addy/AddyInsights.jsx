import React, { useState } from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import { router } from '@inertiajs/react';
import StackedCards from '@/Components/StackedCards';
import { Star, Lightbulb, AlertTriangle, TrendingUp, CheckCircle, XCircle, Sparkles, ArrowRight, RefreshCw, MessageCircle, X } from 'lucide-react';

export default function AddyInsights() {
    const addyContext = useAddy();
    
    // If context is not available, don't render
    if (!addyContext) {
        return null;
    }
    
    const { isOpen, closeAddy, topInsight, insights: initialInsights, state, addy, showChatView, refreshInsights, dismissInsight } = addyContext;
    const [refreshing, setRefreshing] = useState(false);
    const [refreshMessage, setRefreshMessage] = useState(null);
    const [localInsights, setLocalInsights] = useState(initialInsights || []);

    // Sync local insights when context insights change
    React.useEffect(() => {
        setLocalInsights(initialInsights || []);
    }, [initialInsights]);

    if (!isOpen) return null;

    const getMoodEmoji = (mood) => {
        const moods = {
            optimistic: '😊',
            neutral: '🤔',
            attentive: '👀',
            concerned: '😟',
            urgent: '🚨',
        };
        return moods[mood] || '🤖';
    };

    const getSystemStatus = (state) => {
        if (!state) return { 
            message: 'Initializing...', 
            color: 'gray',
            bgClass: 'bg-gray-100',
            textClass: 'text-gray-700',
        };
        
        const urgency = state.urgency || 0;
        const mood = state.mood || 'neutral';
        const context = (state.context || '').toLowerCase();
        
        if (urgency < 0.2 && (mood === 'optimistic' || context.includes('ahead') || context.includes('exceeding'))) {
            return {
                message: "You're ahead and winning!",
                color: 'gold',
                bgClass: 'bg-amber-50 border-amber-200',
                textClass: 'text-amber-700',
                icon: '⭐',
            };
        }
        
        if (urgency < 0.4 && (mood === 'neutral' || mood === 'optimistic' || mood === 'attentive')) {
            return {
                message: "We're good!",
                color: 'green',
                bgClass: 'bg-emerald-50 border-emerald-200',
                textClass: 'text-emerald-700',
                icon: '✅',
            };
        }
        
        if (urgency >= 0.4 && urgency < 0.7) {
            return {
                message: "Things need attention",
                color: 'orange',
                bgClass: 'bg-orange-50 border-orange-200',
                textClass: 'text-orange-700',
                icon: '⚠️',
            };
        }
        
        if (urgency >= 0.7 || mood === 'urgent' || mood === 'concerned') {
            return {
                message: "We should be worried",
                color: 'red',
                bgClass: 'bg-red-50 border-red-200',
                textClass: 'text-red-700',
                icon: '🚨',
            };
        }
        
        return {
            message: "Monitoring your business",
            color: 'gray',
            bgClass: 'bg-gray-50 border-gray-200',
            textClass: 'text-gray-700',
            icon: '📊',
        };
    };

    const handleRefresh = async () => {
        setRefreshing(true);
        setRefreshMessage(null);
        
        try {
            const result = await refreshInsights();
            setRefreshMessage({
                type: result.success ? 'success' : 'error',
                text: result.message,
            });
            
            setTimeout(() => {
                setRefreshMessage(null);
            }, 3000);
        } catch (error) {
            setRefreshMessage({
                type: 'error',
                text: 'Failed to refresh insights. Please try again.',
            });
        } finally {
            setRefreshing(false);
        }
    };

    const handleDismiss = async (insight, direction) => {
        // Right swipe = Take action
        if (direction === 'right' && (insight.url || insight.action_url)) {
            router.visit(insight.url || insight.action_url);
            closeAddy();
            return;
        }
        
        // Remove from local state immediately
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
                                <p className="text-white/80 text-xs font-medium">Addy Insight</p>
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
                                {insight.url || insight.action_url ? 'Take Action' : 'Got it!'}
                            </span>
                            <CheckCircle className="w-4 h-4" />
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const status = getSystemStatus(state);

    return (
        <>
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-black/30 backdrop-blur-sm z-50"
                onClick={closeAddy}
            />

            {/* Insights Container */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div className="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
                    
                    {/* Header */}
                    <div className="bg-white border-b border-gray-100 p-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                                <img 
                                    src="/assets/logos/icon-white.png" 
                                    alt="Addy" 
                                    className="w-6 h-6 object-contain"
                                />
                            </div>
                            <div>
                                <h2 className="font-bold text-lg text-gray-900">Addy Insights</h2>
                                <p className="text-xs text-gray-500">
                                    {localInsights.length > 0 
                                        ? `${localInsights.length} insight${localInsights.length !== 1 ? 's' : ''} to review`
                                        : 'All caught up!'
                                    }
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                onClick={handleRefresh}
                                disabled={refreshing}
                                className="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors disabled:opacity-50"
                                title="Refresh insights"
                            >
                                <RefreshCw className={`w-5 h-5 ${refreshing ? 'animate-spin' : ''}`} />
                            </button>
                            <button
                                onClick={showChatView}
                                className="p-2 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-600 transition-colors"
                                title="Open chat"
                            >
                                <MessageCircle className="w-5 h-5" />
                            </button>
                            <button
                                onClick={closeAddy}
                                className="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors"
                                title="Close"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    {/* Refresh Message */}
                    {refreshMessage && (
                        <div className={`mx-4 mt-4 p-3 rounded-xl text-sm font-medium ${
                            refreshMessage.type === 'success' 
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' 
                                : 'bg-red-50 text-red-700 border border-red-200'
                        }`}>
                            {refreshMessage.text}
                        </div>
                    )}

                    {/* System Status Bar */}
                    {state && (
                        <div className={`mx-4 mt-4 p-4 rounded-xl border ${status.bgClass}`}>
                            <div className="flex items-center gap-3">
                                <span className="text-2xl">{status.icon}</span>
                                <div>
                                    <p className={`font-semibold ${status.textClass}`}>{status.message}</p>
                                    {state.focus_area && (
                                        <p className="text-sm text-gray-500">Focus: {state.focus_area}</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Content - Swipeable Cards */}
                    <div className="flex-1 flex items-center justify-center p-6 bg-gray-50/50" style={{ minHeight: '480px' }}>
                        {localInsights && localInsights.length > 0 ? (
                            <StackedCards
                                cards={localInsights}
                                renderCard={renderInsightCard}
                                onDismiss={handleDismiss}
                                maxVisible={3}
                                cardGap={10}
                                scaleStep={0.04}
                            />
                        ) : (
                            <div className="text-center">
                                <div className="w-20 h-20 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center">
                                    <Sparkles className="w-10 h-10 text-teal-500" />
                                </div>
                                <h3 className="text-xl font-bold text-teal-700 mb-2">All Clear! ✨</h3>
                                <p className="text-gray-600 max-w-sm">
                                    Addy is monitoring your business. I'll let you know if anything needs attention.
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Instructions */}
                    {localInsights && localInsights.length > 0 && (
                        <div className="px-6 pb-4 text-center border-t border-gray-100 pt-3 bg-white">
                            <p className="text-gray-400 text-sm">
                                ← Swipe left to dismiss • Swipe right to take action →
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
