import React, { useState, useEffect, useRef } from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import axios from 'axios';
import { router } from '@inertiajs/react';
import ActionConfirmation from './ActionConfirmation';

export default function AddyChat() {
    const { isOpen, closeAddy } = useAddy();
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [sending, setSending] = useState(false);
    const [loading, setLoading] = useState(true);
    const messagesEndRef = useRef(null);

    // Load chat history on mount
    useEffect(() => {
        if (isOpen) {
            loadHistory();
        }
    }, [isOpen]);

    // Auto-scroll to bottom
    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const loadHistory = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/api/addy/chat/history');
            setMessages(response.data);
        } catch (error) {
            console.error('Failed to load chat history:', error);
        } finally {
            setLoading(false);
        }
    };

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const sendMessage = async (messageText = null) => {
        const textToSend = messageText || input.trim();
        if (!textToSend || sending) return;

        setSending(true);
        setInput('');

        // Add user message to UI immediately
        const userMessage = {
            id: Date.now(),
            role: 'user',
            content: textToSend,
            created_at: new Date().toISOString(),
        };
        setMessages(prev => [...prev, userMessage]);

        try {
            const response = await axios.post('/api/addy/chat', {
                message: textToSend,
            });

            // Add assistant response
            setMessages(prev => [...prev, response.data.message]);
        } catch (error) {
            console.error('Failed to send message:', error);
            // Add error message
            setMessages(prev => [...prev, {
                id: Date.now() + 1,
                role: 'assistant',
                content: "Sorry, I'm having trouble responding right now. Please try again.",
                created_at: new Date().toISOString(),
            }]);
        } finally {
            setSending(false);
        }
    };

    const handleQuickAction = (action) => {
        if (action.command) {
            sendMessage(action.command);
        } else if (action.url) {
            router.visit(action.url);
            closeAddy();
        }
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    const clearHistory = async () => {
        if (!confirm('Clear all chat history?')) return;

        try {
            await axios.delete('/api/addy/chat/history');
            setMessages([]);
        } catch (error) {
            console.error('Failed to clear history:', error);
        }
    };

    if (!isOpen) return null;

    return (
        <>
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-gradient-to-br from-teal-50/30 via-mint-50/20 to-white/40 backdrop-blur-md z-50"
                onClick={closeAddy}
            />

            {/* Chat Container */}
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
                                <h2 className="font-bold text-lg text-teal-700">Addy</h2>
                                <p className="text-xs text-teal-600/70">Your Business COO</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                onClick={clearHistory}
                                className="p-2 rounded-xl bg-white/60 hover:bg-white/80 backdrop-blur-sm border border-mint-200/50 text-teal-600 hover:text-teal-700 transition-all shadow-sm"
                                title="Clear history"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
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

                    {/* Messages */}
                    <div className="flex-1 overflow-y-auto p-6 space-y-4" style={{
                        background: 'linear-gradient(180deg, rgba(255,255,255,0.5) 0%, rgba(240,253,250,0.3) 50%, rgba(255,255,255,0.5) 100%)'
                    }}>
                        {loading ? (
                            <div className="flex items-center justify-center h-full">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500 border-t-transparent"></div>
                            </div>
                        ) : messages.length === 0 ? (
                            <div className="flex flex-col items-center justify-center h-full text-center">
                                <div className="text-6xl mb-4">👋</div>
                                <h3 className="text-xl font-semibold text-gray-700 mb-2">
                                    Hi! I'm Addy
                                </h3>
                                <p className="text-gray-500 mb-6 max-w-md">
                                    I'm your business COO. Ask me anything about your finances, sales, team, or inventory.
                                </p>

                                <div className="grid grid-cols-2 gap-3">
                                    <button
                                        onClick={() => sendMessage('What is my cash position?')}
                                        className="px-4 py-3 bg-white/70 backdrop-blur-sm border border-mint-200/50 text-teal-700 rounded-xl hover:bg-mint-50/80 hover:border-mint-300/70 transition-all shadow-sm text-sm font-medium"
                                    >
                                        💰 Cash Position
                                    </button>
                                    <button
                                        onClick={() => sendMessage('Show me top expenses')}
                                        className="px-4 py-3 bg-white/70 backdrop-blur-sm border border-mint-200/50 text-teal-700 rounded-xl hover:bg-mint-50/80 hover:border-mint-300/70 transition-all shadow-sm text-sm font-medium"
                                    >
                                        📊 Top Expenses
                                    </button>
                                    <button
                                        onClick={() => sendMessage('What should I focus on today?')}
                                        className="px-4 py-3 bg-white/70 backdrop-blur-sm border border-mint-200/50 text-teal-700 rounded-xl hover:bg-mint-50/80 hover:border-mint-300/70 transition-all shadow-sm text-sm font-medium"
                                    >
                                        🎯 Daily Focus
                                    </button>
                                    <button
                                        onClick={() => sendMessage('Show me overdue invoices')}
                                        className="px-4 py-3 bg-white/70 backdrop-blur-sm border border-mint-200/50 text-teal-700 rounded-xl hover:bg-mint-50/80 hover:border-mint-300/70 transition-all shadow-sm text-sm font-medium"
                                    >
                                        📄 Overdue Invoices
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <>
                                {messages.map((message, index) => (
                                    <div
                                        key={message.id || index}
                                        className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                                    >
                                        <div className={`max-w-[80%] ${message.role === 'user' ? 'order-2' : ''}`}>
                                            {/* Message bubble */}
                                            <div
                                                className={`rounded-2xl px-4 py-3 backdrop-blur-sm shadow-lg ${
                                                    message.role === 'user'
                                                        ? 'bg-gradient-to-br from-teal-500 to-teal-600 text-white border border-teal-400/30'
                                                        : 'bg-white/70 text-gray-900 border border-mint-200/50'
                                                }`}
                                            >
                                                <div className="whitespace-pre-wrap">{message.content}</div>
                                            </div>

                                            {/* Action Confirmation */}
                                            {message.role === 'assistant' && message.metadata?.action && (
                                                <ActionConfirmation
                                                    action={message.metadata.action}
                                                    onConfirm={(result) => {
                                                        // Reload messages to show result
                                                        loadHistory();
                                                    }}
                                                    onCancel={() => {
                                                        // Optionally reload
                                                    }}
                                                />
                                            )}

                                            {/* Quick actions */}
                                            {message.role === 'assistant' && message.metadata?.quick_actions && (
                                                <div className="flex flex-wrap gap-2 mt-2">
                                                    {message.metadata.quick_actions.map((action, idx) => (
                                                        <button
                                                            key={idx}
                                                            onClick={() => {
                                                                if (action.type === 'confirm' && action.action_id) {
                                                                    // Handle action confirmation via ActionConfirmation component
                                                                    return;
                                                                }
                                                                handleQuickAction(action);
                                                            }}
                                                            className="px-3 py-1.5 bg-white/80 backdrop-blur-sm border border-mint-200/50 rounded-full text-xs text-teal-700 hover:bg-mint-50/80 hover:border-mint-300/70 transition-all shadow-sm"
                                                        >
                                                            {action.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                        </div>

                                        {/* Avatar */}
                                        {message.role === 'assistant' && (
                                            <div className="w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm border border-mint-200/50 shadow-sm flex items-center justify-center mr-2 flex-shrink-0">
                                                <img 
                                                    src="/assets/logos/icon.png" 
                                                    alt="Addy" 
                                                    className="w-5 h-5 object-contain"
                                                />
                                            </div>
                                        )}
                                    </div>
                                ))}

                                {sending && (
                                    <div className="flex justify-start">
                                        <div className="w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm border border-mint-200/50 shadow-sm flex items-center justify-center mr-2">
                                            <img 
                                                src="/assets/logos/icon.png" 
                                                alt="Addy" 
                                                className="w-5 h-5 object-contain"
                                            />
                                        </div>
                                        <div className="bg-white/70 backdrop-blur-sm border border-mint-200/50 rounded-2xl px-4 py-3 shadow-lg">
                                            <div className="flex space-x-2">
                                                <div className="w-2 h-2 bg-teal-500 rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></div>
                                                <div className="w-2 h-2 bg-mint-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></div>
                                                <div className="w-2 h-2 bg-teal-500 rounded-full animate-bounce" style={{ animationDelay: '300ms' }}></div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div ref={messagesEndRef} />
                            </>
                        )}
                    </div>

                    {/* Input */}
                    <div className="border-t border-mint-200/40 backdrop-blur-md p-4 rounded-b-3xl" style={{
                        background: 'linear-gradient(180deg, rgba(240,253,250,0.4) 0%, rgba(255,255,255,0.6) 100%)'
                    }}>
                        <div className="flex gap-2">
                            <textarea
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                onKeyPress={handleKeyPress}
                                placeholder="Ask me anything..."
                                rows="1"
                                className="flex-1 px-4 py-3 bg-white/80 backdrop-blur-sm border border-mint-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 resize-none text-gray-900 placeholder:text-gray-400 shadow-sm"
                                disabled={sending}
                            />
                            <button
                                onClick={() => sendMessage()}
                                disabled={!input.trim() || sending}
                                className="px-6 py-3 bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-xl hover:from-teal-600 hover:to-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>

                        <p className="text-xs text-teal-600/60 mt-2">
                            Press Enter to send, Shift+Enter for new line
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

