import React from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import { router } from '@inertiajs/react';

export default function AddyInsights() {
    const { isOpen, closeAddy, topInsight, state, addy, showChatView } = useAddy();

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

    const getUrgencyColor = (urgency) => {
        if (urgency >= 0.8) return 'text-red-600';
        if (urgency >= 0.6) return 'text-orange-600';
        if (urgency >= 0.4) return 'text-yellow-600';
        return 'text-green-600';
    };

    const handleActionClick = (url) => {
        if (url) {
            router.visit(url);
            closeAddy();
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
                        
                        {/* Current State */}
                        {state && (
                            <div className="bg-white/70 backdrop-blur-sm border border-mint-200/50 rounded-2xl p-6 shadow-lg">
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="text-xl font-semibold text-gray-800">Current Focus</h2>
                                    <span className={`text-sm font-medium px-3 py-1 rounded-full ${getUrgencyColor(state.urgency)} bg-white/60`}>
                                        Urgency: {Math.round(state.urgency * 100)}%
                                    </span>
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

                                {topInsight.url && (
                                    <div className="mt-6">
                                        <button
                                            onClick={() => handleActionClick(topInsight.url)}
                                            className="w-full bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-semibold py-3 px-6 rounded-xl transition-all shadow-lg hover:shadow-xl"
                                        >
                                            Take Action →
                                        </button>
                                    </div>
                                )}
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
