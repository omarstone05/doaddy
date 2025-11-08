import React from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import { router } from '@inertiajs/react';

export default function AddyInsights() {
    const { isOpen, closeAddy, topInsight, state, addy } = useAddy();

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
                className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 transition-opacity duration-300"
                onClick={closeAddy}
            />

            {/* Panel */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div className="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col animate-in fade-in zoom-in duration-300">
                    
                    {/* Header */}
                    <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 relative">
                        {/* Close button - TOP RIGHT */}
                        <button
                            onClick={closeAddy}
                            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors"
                            title="Close"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div className="flex items-center gap-4">
                            <div className="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-3xl">
                                {getMoodEmoji(state?.mood)}
                            </div>
                            <div>
                                <h1 className="text-3xl font-bold">Addy</h1>
                                <p className="text-blue-100 text-sm">Your Business COO</p>
                            </div>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="flex-1 overflow-y-auto p-6 space-y-6">
                        
                        {/* Current State */}
                        {state && (
                            <div className="bg-gray-50 rounded-xl p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="text-xl font-semibold text-gray-800">Current Focus</h2>
                                    <span className={`text-sm font-medium ${getUrgencyColor(state.urgency)}`}>
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
                                                        <span className="text-blue-600">•</span>
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
                            <div className="bg-white border-2 border-blue-200 rounded-xl p-6">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="flex items-center gap-3">
                                        <div className={`
                                            px-3 py-1 rounded-full text-xs font-semibold uppercase
                                            ${topInsight.type === 'alert' ? 'bg-red-100 text-red-700' : ''}
                                            ${topInsight.type === 'suggestion' ? 'bg-blue-100 text-blue-700' : ''}
                                            ${topInsight.type === 'observation' ? 'bg-green-100 text-green-700' : ''}
                                            ${topInsight.type === 'achievement' ? 'bg-purple-100 text-purple-700' : ''}
                                        `}>
                                            {topInsight.type}
                                        </div>
                                        <h3 className="text-xl font-bold text-gray-900">{topInsight.title}</h3>
                                    </div>
                                    <span className="text-sm font-medium text-gray-500">
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
                                                    <span className="text-blue-600 font-bold">→</span>
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
                                            className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
                                        >
                                            Take Action →
                                        </button>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Additional Insights */}
                        {addy?.insights_count > 1 && (
                            <div className="bg-gray-50 rounded-xl p-6">
                                <p className="text-gray-600 text-center">
                                    You have <span className="font-semibold text-gray-900">{addy.insights_count - 1} more insight{addy.insights_count - 1 !== 1 ? 's' : ''}</span> waiting
                                </p>
                                <p className="text-sm text-gray-500 text-center mt-2">
                                    Full insights dashboard coming soon
                                </p>
                            </div>
                        )}

                        {/* No insights */}
                        {!topInsight && (
                            <div className="text-center py-12">
                                <div className="text-6xl mb-4">✨</div>
                                <h3 className="text-xl font-semibold text-gray-700 mb-2">All Clear!</h3>
                                <p className="text-gray-500">Addy is monitoring your business. I'll let you know if anything needs attention.</p>
                            </div>
                        )}
                    </div>

                    {/* Footer */}
                    <div className="bg-gray-50 border-t border-gray-200 p-4 text-center text-sm text-gray-500">
                        <p>Addy learns from your business patterns and adapts to your rhythm</p>
                    </div>
                </div>
            </div>
        </>
    );
}

