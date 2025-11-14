import React, { useState } from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import { router } from '@inertiajs/react';

export default function AddyInsights() {
    const { isOpen, closeAddy, topInsight, state, addy, showChatView, refreshInsights, dismissInsight } = useAddy();
    const [refreshing, setRefreshing] = useState(false);
    const [refreshMessage, setRefreshMessage] = useState(null);
    const [dismissing, setDismissing] = useState(false);

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
            bgClass: 'bg-gradient-to-r from-gray-400 to-slate-500',
            textClass: 'text-gray-900',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            ),
        };
        
        const urgency = state.urgency || 0;
        const mood = state.mood || 'neutral';
        const context = (state.context || '').toLowerCase();
        
        // Gold: Ahead and winning (very low urgency + optimistic mood + positive indicators)
        if (urgency < 0.2 && (mood === 'optimistic' || context.includes('ahead') || context.includes('exceeding') || context.includes('strong'))) {
            return {
                message: "You're ahead and really winning!",
                color: 'gold',
                bgClass: 'bg-gradient-to-r from-yellow-400 to-amber-500',
                textClass: 'text-yellow-900',
                icon: (
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                ),
            };
        }
        
        // Green: All good (low urgency + neutral/optimistic mood)
        if (urgency < 0.4 && (mood === 'neutral' || mood === 'optimistic' || mood === 'attentive')) {
            return {
                message: "We're good!",
                color: 'green',
                bgClass: 'bg-gradient-to-r from-green-400 to-emerald-500',
                textClass: 'text-green-900',
                icon: (
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                ),
            };
        }
        
        // Yellow/Orange: A little behind (medium urgency)
        if (urgency >= 0.4 && urgency < 0.7) {
            if (mood === 'concerned' || context.includes('behind') || context.includes('lagging')) {
                return {
                    message: "You're a little behind",
                    color: 'yellow',
                    bgClass: 'bg-gradient-to-r from-yellow-400 to-orange-400',
                    textClass: 'text-yellow-900',
                    icon: (
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    ),
                };
            }
            return {
                message: "Things need attention",
                color: 'orange',
                bgClass: 'bg-gradient-to-r from-orange-400 to-amber-500',
                textClass: 'text-orange-900',
                icon: (
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                ),
            };
        }
        
        // Red: In trouble (high urgency + concerned/urgent mood)
        if (urgency >= 0.7 || mood === 'urgent' || mood === 'concerned' || context.includes('urgent') || context.includes('critical')) {
            return {
                message: "We should be worried",
                color: 'red',
                bgClass: 'bg-gradient-to-r from-red-400 to-rose-600',
                textClass: 'text-red-900',
                icon: (
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                ),
            };
        }
        
        // Default fallback
        return {
            message: "Monitoring your business",
            color: 'gray',
            bgClass: 'bg-gradient-to-r from-gray-400 to-slate-500',
            textClass: 'text-gray-900',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            ),
        };
    };

    const handleActionClick = (url) => {
        if (url) {
            router.visit(url);
            closeAddy();
        }
    };

    const handleDismiss = async () => {
        if (!topInsight?.id) return;
        
        setDismissing(true);
        try {
            await dismissInsight(topInsight.id);
            // The dismissInsight function will reload the page, so we don't need to do anything else
        } catch (error) {
            console.error('Failed to dismiss insight:', error);
            setDismissing(false);
            setRefreshMessage({
                type: 'error',
                text: error.response?.data?.message || 'Failed to dismiss insight. Please try again.',
            });
            // Clear error message after 5 seconds
            setTimeout(() => {
                setRefreshMessage(null);
            }, 5000);
        }
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
            
            // Clear message after 3 seconds
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

    return (
        <>
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-gradient-to-br from-teal-50/30 via-mint-50/20 to-white/40 backdrop-blur-md z-50"
                onClick={closeAddy}
            />

            {/* Insights Container */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div className="bg-white/90 backdrop-blur-2xl rounded-3xl shadow-2xl border border-white/60 w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden relative" style={{
                    background: 'linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(240,253,250,0.9) 50%, rgba(255,255,255,0.95) 100%)'
                }}>
                    
                    {/* Header */}
                    <div className="bg-white/40 backdrop-blur-md border-b border-mint-200/40 p-4 rounded-t-3xl flex items-center justify-between" style={{
                        background: 'linear-gradient(180deg, rgba(255,255,255,0.6) 0%, rgba(240,253,250,0.4) 100%)'
                    }}>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm shadow-md flex items-center justify-center border border-mint-200/30">
                                <img 
                                    src="/assets/logos/icon.png" 
                                    alt="Addy" 
                                    className="w-7 h-7 object-contain"
                                />
                            </div>
                            <div>
                                <h2 className="font-bold text-lg text-teal-700">Addy Insights</h2>
                                <p className="text-xs text-teal-600/70">Your Business COO</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                onClick={handleRefresh}
                                disabled={refreshing}
                                className="px-3 py-2 rounded-xl bg-white/70 hover:bg-white text-teal-600 border border-mint-200/60 text-sm font-semibold transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                title="Refresh insights with latest data"
                            >
                                {refreshing ? (
                                    <>
                                        <svg className="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Refreshing...
                                    </>
                                ) : (
                                    <>
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Refresh
                                    </>
                                )}
                            </button>
                            <button
                                onClick={showChatView}
                                className="px-3 py-2 rounded-xl bg-white/70 hover:bg-white text-teal-600 border border-mint-200/60 text-sm font-semibold transition-all shadow-sm"
                                title="Back to chat"
                            >
                                Chat
                            </button>
                            <button
                                onClick={closeAddy}
                                className="p-2 rounded-xl bg-white/60 hover:bg-white/80 backdrop-blur-sm border border-mint-200/50 text-teal-700 hover:text-teal-800 transition-all shadow-sm"
                                title="Close"
                            >
                                <svg className="w-5 h-5 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="flex-1 overflow-y-auto p-6 space-y-4" style={{
                        background: 'linear-gradient(180deg, rgba(255,255,255,0.5) 0%, rgba(240,253,250,0.3) 50%, rgba(255,255,255,0.5) 100%)'
                    }}>
                        {/* Refresh Message */}
                        {refreshMessage && (
                            <div className={`p-4 rounded-xl ${
                                refreshMessage.type === 'success' 
                                    ? 'bg-green-50 border border-green-200 text-green-800' 
                                    : 'bg-red-50 border border-red-200 text-red-800'
                            }`}>
                                <p className="text-sm font-medium">{refreshMessage.text}</p>
                            </div>
                        )}
                        
                        {/* System Status */}
                        {state && (() => {
                            const status = getSystemStatus(state);
                            return (
                                <div className={`${status.bgClass} rounded-2xl p-6 shadow-lg border-2 border-white/30`}>
                                    <div className="flex items-center justify-between mb-4">
                                        <h2 className="text-2xl font-bold text-white">System Status</h2>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <div className="text-white">
                                            {status.icon}
                                        </div>
                                        <p className={`text-xl font-semibold ${status.textClass}`}>
                                            {status.message}
                                        </p>
                                    </div>
                                </div>
                            );
                        })()}

                        {/* Current State */}
                        {state && (
                            <div className="bg-white/70 backdrop-blur-sm border border-mint-200/50 rounded-2xl p-6 shadow-lg">
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="text-xl font-semibold text-gray-800">Current Focus</h2>
                                </div>
                                
                                <div className="space-y-3">
                                    <div>
                                        <span className="text-sm text-gray-500">Area:</span>
                                        <p className="text-lg font-medium text-gray-900">{state.focus_area}</p>
                                    </div>
                                    
                                    <div>
                                        <span className="text-sm text-gray-500">Context:</span>
                                        <p className="text-gray-700">{state.context}</p>
                                    </div>

                                    {state.priorities && state.priorities.length > 0 && (
                                        <div>
                                            <span className="text-sm text-gray-500">Priorities:</span>
                                            <ul className="mt-2 space-y-1">
                                                {state.priorities.map((priority, index) => (
                                                    <li key={index} className="flex items-start gap-2 text-gray-700">
                                                        <span className="text-teal-600">•</span>
                                                        <span>{priority}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}

                                    {state.last_updated && (
                                        <div className="text-xs text-gray-400 mt-2">
                                            Last updated: {state.last_updated}
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Top Insight */}
                        {topInsight && (
                            <div className="bg-white/70 backdrop-blur-sm border border-mint-200/50 rounded-2xl p-6 shadow-lg">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="flex items-center gap-3 flex-wrap">
                                        <div className={`
                                            px-3 py-1 rounded-full text-xs font-semibold uppercase
                                            ${topInsight.type === 'alert' ? 'bg-red-100 text-red-700' : ''}
                                            ${topInsight.type === 'suggestion' ? 'bg-teal-100 text-teal-700' : ''}
                                            ${topInsight.type === 'observation' ? 'bg-green-100 text-green-700' : ''}
                                            ${topInsight.type === 'achievement' ? 'bg-purple-100 text-purple-700' : ''}
                                        `}>
                                            {topInsight.type}
                                        </div>
                                        <h3 className="text-xl font-bold text-gray-900">{topInsight.title}</h3>
                                    </div>
                                    <span className="text-sm font-medium px-3 py-1 rounded-full text-gray-600 bg-white/60">
                                        Priority: {Math.round(topInsight.priority * 100)}%
                                    </span>
                                </div>

                                <div className="prose prose-sm max-w-none">
                                    <p className="text-gray-700 whitespace-pre-line">{topInsight.description}</p>
                                </div>

                                {topInsight.actions && topInsight.actions.length > 0 && (
                                    <div className="mt-4">
                                        <p className="text-sm font-semibold text-gray-700 mb-2">Suggested Actions:</p>
                                        <ul className="space-y-2">
                                            {topInsight.actions.map((action, index) => (
                                                <li key={index} className="flex items-start gap-2 text-gray-600">
                                                    <span className="text-teal-600 font-bold">→</span>
                                                    <span>{action}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                <div className="mt-6 flex gap-3">
                                    {topInsight.url && (
                                        <button
                                            onClick={() => handleActionClick(topInsight.url)}
                                            className="flex-1 bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-semibold py-3 px-6 rounded-xl transition-all shadow-lg hover:shadow-xl"
                                        >
                                            Take Action →
                                        </button>
                                    )}
                                    {topInsight && (
                                        <button
                                            onClick={handleDismiss}
                                            disabled={dismissing || !topInsight.id}
                                            className={`${topInsight.url ? 'px-6' : 'flex-1 px-6'} py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-all shadow-sm hover:shadow disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2`}
                                            title="Dismiss this insight"
                                        >
                                            {dismissing ? (
                                                <>
                                                    <svg className="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Dismissing...
                                                </>
                                            ) : (
                                                <>
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Dismiss
                                                </>
                                            )}
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Additional Insights */}
                        {addy?.insights_count > 1 && (
                            <div className="bg-white/70 backdrop-blur-sm border border-mint-200/50 rounded-2xl p-6 shadow-lg">
                                <p className="text-gray-600 text-center">
                                    You have <span className="font-semibold text-gray-900">{addy.insights_count - 1} more insight{addy.insights_count - 1 !== 1 ? 's' : ''}</span> waiting
                                </p>
                                <p className="text-sm text-gray-500 text-center mt-2">
                                    Full insights dashboard coming soon
                                </p>
                            </div>
                        )}

                        {/* No insights */}
                        {!topInsight && !state && (
                            <div className="flex flex-col items-center justify-center h-full text-center">
                                <div className="text-6xl mb-4">✨</div>
                                <h3 className="text-xl font-semibold text-gray-700 mb-2">All Clear!</h3>
                                <p className="text-gray-500 max-w-md">Addy is monitoring your business. I'll let you know if anything needs attention.</p>
                            </div>
                        )}
                    </div>

                    {/* Footer */}
                    <div className="border-t border-mint-200/40 backdrop-blur-md p-4 text-center text-sm text-teal-600/60 rounded-b-3xl" style={{
                        background: 'linear-gradient(180deg, rgba(240,253,250,0.4) 0%, rgba(255,255,255,0.6) 100%)'
                    }}>
                        <p>Addy learns from your business patterns and adapts to your rhythm</p>
                    </div>
                </div>
            </div>
        </>
    );
}
