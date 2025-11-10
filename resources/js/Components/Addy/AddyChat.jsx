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
                className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50"
                onClick={closeAddy}
            />

            {/* Chat Container */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div className="bg-white rounded-2xl shadow-2xl w-full max-w-4xl h-[90vh] flex flex-col">
                    
                    {/* Header */}
                    <div className="bg-gradient-to-r from-teal-500 to-mint-300 text-white p-4 rounded-t-2xl flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                                <img 
                                    src="/assets/logos/icon-white.png" 
                                    alt="Addy" 
                                    className="w-6 h-6 object-contain"
                                />
                            </div>
                            <div>
                                <h2 className="font-bold text-lg">Addy</h2>
                                <p className="text-xs text-white/80">Your Business COO</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                onClick={clearHistory}
                                className="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition"
                                title="Clear history"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>

                            <button
                                onClick={closeAddy}
                                className="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition"
                                title="Close"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Messages */}
                    <div className="flex-1 overflow-y-auto p-6 space-y-4">
                        {loading ? (
                            <div className="flex items-center justify-center h-full">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500"></div>
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
                                        className="px-4 py-3 bg-teal-50 text-teal-700 rounded-lg hover:bg-teal-100 transition text-sm"
                                    >
                                        💰 Cash Position
                                    </button>
                                    <button
                                        onClick={() => sendMessage('Show me top expenses')}
                                        className="px-4 py-3 bg-mint-50 text-teal-700 rounded-lg hover:bg-mint-100 transition text-sm"
                                    >
                                        📊 Top Expenses
                                    </button>
                                    <button
                                        onClick={() => sendMessage('What should I focus on today?')}
                                        className="px-4 py-3 bg-teal-50 text-teal-700 rounded-lg hover:bg-teal-100 transition text-sm"
                                    >
                                        🎯 Daily Focus
                                    </button>
                                    <button
                                        onClick={() => sendMessage('Show me overdue invoices')}
                                        className="px-4 py-3 bg-mint-50 text-teal-700 rounded-lg hover:bg-mint-100 transition text-sm"
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
                                                className={`rounded-2xl px-4 py-3 ${
                                                    message.role === 'user'
                                                        ? 'bg-teal-500 text-white'
                                                        : 'bg-gray-100 text-gray-900'
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
                                                            className="px-3 py-1 bg-white border border-gray-300 rounded-full text-xs text-gray-700 hover:bg-gray-50 transition"
                                                        >
                                                            {action.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                        </div>

                                        {/* Avatar */}
                                        {message.role === 'assistant' && (
                                            <div className="w-8 h-8 rounded-full bg-gradient-to-r from-teal-500 to-mint-300 flex items-center justify-center mr-2 flex-shrink-0">
                                                <img 
                                                    src="/assets/logos/icon-white.png" 
                                                    alt="Addy" 
                                                    className="w-5 h-5 object-contain"
                                                />
                                            </div>
                                        )}
                                    </div>
                                ))}

                                {sending && (
                                    <div className="flex justify-start">
                                        <div className="w-8 h-8 rounded-full bg-gradient-to-r from-teal-500 to-mint-300 flex items-center justify-center mr-2">
                                            <img 
                                                src="/assets/logos/icon-white.png" 
                                                alt="Addy" 
                                                className="w-5 h-5 object-contain"
                                            />
                                        </div>
                                        <div className="bg-gray-100 rounded-2xl px-4 py-3">
                                            <div className="flex space-x-2">
                                                <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></div>
                                                <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></div>
                                                <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }}></div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div ref={messagesEndRef} />
                            </>
                        )}
                    </div>

                    {/* Input */}
                    <div className="border-t p-4">
                        <div className="flex gap-2">
                            <textarea
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                onKeyPress={handleKeyPress}
                                placeholder="Ask me anything..."
                                rows="1"
                                className="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none"
                                disabled={sending}
                            />
                            <button
                                onClick={() => sendMessage()}
                                disabled={!input.trim() || sending}
                                className="px-6 py-3 bg-teal-500 text-white rounded-lg hover:bg-teal-600 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>

                        <p className="text-xs text-gray-500 mt-2">
                            Press Enter to send, Shift+Enter for new line
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

