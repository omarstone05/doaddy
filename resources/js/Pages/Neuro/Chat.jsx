import React, { useState, useRef, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

/**
 * Neuro Chat - Standalone chat interface for testing NeuroCore
 * 
 * This is SEPARATE from the main Addy chat interface.
 * Use it to test the Neuro helper without affecting existing functionality.
 */
export default function NeuroChat({ auth }) {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [goals, setGoals] = useState([]);
    const [showGoals, setShowGoals] = useState(false);
    const messagesEndRef = useRef(null);

    // Auto-scroll to bottom
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    // Load goals on mount
    useEffect(() => {
        loadGoals();
    }, []);

    const loadGoals = async () => {
        try {
            const response = await fetch('/api/neuro/goals', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            });
            const data = await response.json();
            if (data.success) {
                setGoals(data.data || []);
            }
        } catch (error) {
            console.error('Failed to load goals:', error);
        }
    };

    const sendMessage = async (e) => {
        e.preventDefault();
        if (!input.trim() || loading) return;

        const userMessage = input.trim();
        setInput('');
        setLoading(true);

        // Add user message to chat
        setMessages(prev => [...prev, {
            role: 'user',
            content: userMessage,
            timestamp: new Date().toISOString(),
        }]);

        try {
            const response = await fetch('/api/neuro/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify({ message: userMessage }),
            });

            const data = await response.json();

            if (data.success) {
                // Add assistant message
                setMessages(prev => [...prev, {
                    role: 'assistant',
                    content: data.data.content,
                    assistanceType: data.data.assistance_type,
                    quickActions: data.data.quick_actions,
                    goalSuggestion: data.data.goal_suggestion,
                    timestamp: new Date().toISOString(),
                }]);

                // Refresh goals if a new one was suggested
                if (data.data.goal_suggestion) {
                    loadGoals();
                }
            } else {
                setMessages(prev => [...prev, {
                    role: 'error',
                    content: data.error || 'Something went wrong',
                    timestamp: new Date().toISOString(),
                }]);
            }
        } catch (error) {
            setMessages(prev => [...prev, {
                role: 'error',
                content: 'Failed to connect to Neuro',
                timestamp: new Date().toISOString(),
            }]);
        } finally {
            setLoading(false);
        }
    };

    const handleQuickAction = (action) => {
        if (action.command) {
            setInput(action.command);
        } else if (action.url) {
            window.location.href = action.url;
        }
    };

    const trackGoal = async (description) => {
        try {
            const response = await fetch('/api/neuro/goals', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify({ description }),
            });
            const data = await response.json();
            if (data.success) {
                loadGoals();
                setMessages(prev => [...prev, {
                    role: 'system',
                    content: `✅ Goal tracked: "${description}"`,
                    timestamp: new Date().toISOString(),
                }]);
            }
        } catch (error) {
            console.error('Failed to track goal:', error);
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Neuro Chat (Beta)" />

            <div className="max-w-4xl mx-auto py-6 px-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            🧠 Neuro Chat
                            <span className="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full">
                                Beta
                            </span>
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Your empowering thinking partner - guides without imposing
                        </p>
                    </div>
                    <button
                        onClick={() => setShowGoals(!showGoals)}
                        className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                    >
                        🎯 Goals ({goals.length})
                    </button>
                </div>

                {/* Goals Panel */}
                {showGoals && (
                    <div className="mb-6 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                        <h3 className="font-semibold text-purple-900 dark:text-purple-200 mb-3">
                            Your Active Goals
                        </h3>
                        {goals.length === 0 ? (
                            <p className="text-sm text-purple-700 dark:text-purple-300">
                                No goals tracked yet. Share your aspirations in the chat!
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {goals.map((goal, i) => (
                                    <li key={goal.id || i} className="flex items-center justify-between bg-white dark:bg-gray-800 rounded p-3">
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">
                                                {goal.description}
                                            </p>
                                            {goal.progress > 0 && (
                                                <div className="mt-1 w-32 bg-gray-200 rounded-full h-2">
                                                    <div 
                                                        className="bg-purple-600 h-2 rounded-full"
                                                        style={{ width: `${goal.progress * 100}%` }}
                                                    />
                                                </div>
                                            )}
                                        </div>
                                        <span className={`text-xs px-2 py-1 rounded ${
                                            goal.status === 'achieved' 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-blue-100 text-blue-800'
                                        }`}>
                                            {goal.status}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}

                {/* Chat Container */}
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    {/* Messages */}
                    <div className="h-[500px] overflow-y-auto p-4 space-y-4">
                        {messages.length === 0 && (
                            <div className="text-center text-gray-500 dark:text-gray-400 py-12">
                                <p className="text-4xl mb-4">🧠</p>
                                <p className="font-medium">Welcome to Neuro</p>
                                <p className="text-sm mt-2">
                                    I'm here to help you think through things - not do them for you.
                                </p>
                                <p className="text-sm mt-1">
                                    Share what's on your mind!
                                </p>
                            </div>
                        )}

                        {messages.map((msg, i) => (
                            <div key={i} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                                <div className={`max-w-[80%] rounded-lg p-4 ${
                                    msg.role === 'user' 
                                        ? 'bg-purple-600 text-white' 
                                        : msg.role === 'error'
                                        ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                        : msg.role === 'system'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                                }`}>
                                    {/* Assistance Type Badge */}
                                    {msg.assistanceType && (
                                        <span className="text-xs opacity-70 block mb-1">
                                            {msg.assistanceType}
                                        </span>
                                    )}

                                    {/* Message Content */}
                                    <div className="whitespace-pre-wrap">{msg.content}</div>

                                    {/* Quick Actions */}
                                    {msg.quickActions && msg.quickActions.length > 0 && (
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            {msg.quickActions.map((action, j) => (
                                                <button
                                                    key={j}
                                                    onClick={() => handleQuickAction(action)}
                                                    className="text-xs px-3 py-1 bg-white/20 hover:bg-white/30 rounded-full transition"
                                                >
                                                    {action.label}
                                                </button>
                                            ))}
                                        </div>
                                    )}

                                    {/* Goal Suggestion */}
                                    {msg.goalSuggestion && (
                                        <div className="mt-3 p-2 bg-white/10 rounded">
                                            <p className="text-xs font-medium">🎯 Goal detected:</p>
                                            <p className="text-sm">{msg.goalSuggestion.description}</p>
                                            <button
                                                onClick={() => trackGoal(msg.goalSuggestion.description)}
                                                className="mt-2 text-xs px-3 py-1 bg-white/20 hover:bg-white/30 rounded-full transition"
                                            >
                                                Track this goal
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}

                        {loading && (
                            <div className="flex justify-start">
                                <div className="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
                                    <div className="flex space-x-2">
                                        <div className="w-2 h-2 bg-purple-600 rounded-full animate-bounce" />
                                        <div className="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }} />
                                        <div className="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style={{ animationDelay: '0.4s' }} />
                                    </div>
                                </div>
                            </div>
                        )}

                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input */}
                    <form onSubmit={sendMessage} className="border-t dark:border-gray-700 p-4">
                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                placeholder="Share what's on your mind..."
                                className="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500"
                                disabled={loading}
                            />
                            <button
                                type="submit"
                                disabled={loading || !input.trim()}
                                className="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                Send
                            </button>
                        </div>
                    </form>
                </div>

                {/* Info Footer */}
                <div className="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        Neuro helps you think through challenges and track goals.
                        It guides rather than prescribes.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


