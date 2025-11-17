import React, { useState, useEffect, useRef } from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import axios from 'axios';
import { router } from '@inertiajs/react';
import ActionConfirmation from './ActionConfirmation';
import ReactMarkdown from 'react-markdown';

export default function AddyChat() {
    const addyContext = useAddy();
    
    // If context is not available, don't render
    if (!addyContext) {
        return null;
    }
    
    const { isOpen, closeAddy, showInsightsView } = addyContext;
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [sending, setSending] = useState(false);
    const [loading, setLoading] = useState(true);
    const [selectedFiles, setSelectedFiles] = useState([]);
    const messagesEndRef = useRef(null);
    const fileInputRef = useRef(null);

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
        if ((!textToSend && selectedFiles.length === 0) || sending) return;

        setSending(true);
        const filesToSend = [...selectedFiles];
        setSelectedFiles([]);
        setInput('');

        // Add user message to UI immediately
        const userMessage = {
            id: Date.now(),
            role: 'user',
            content: textToSend || (filesToSend.length > 0 ? `Uploaded ${filesToSend.length} file(s)` : ''),
            attachments: filesToSend.map(f => ({
                file_name: f.name,
                file_size: f.size,
                mime_type: f.type,
            })),
            created_at: new Date().toISOString(),
        };
        setMessages(prev => [...prev, userMessage]);

        try {
            const formData = new FormData();
            if (textToSend) {
                formData.append('message', textToSend);
            }
            filesToSend.forEach((file) => {
                formData.append('files[]', file);
            });

            const response = await axios.post('/api/addy/chat', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            // Check for error in response
            if (response.data.error) {
                setMessages(prev => [...prev, {
                    id: Date.now() + 1,
                    role: 'assistant',
                    content: response.data.error,
                    created_at: new Date().toISOString(),
                }]);
                return;
            }

            // Add assistant response
            if (response.data.message) {
                setMessages(prev => [...prev, response.data.message]);
            }

            // Handle organization creation redirect
            if (response.data.organization_created && response.data.redirect) {
                // Close chat and redirect to onboarding
                closeAddy();
                setTimeout(() => {
                    router.visit(response.data.redirect);
                }, 1000); // Small delay to show the success message
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            // Add error message with more details
            const errorMessage = error.response?.data?.error 
                || error.response?.data?.message 
                || error.message 
                || "Sorry, I'm having trouble responding right now. Please try again.";
            
            setMessages(prev => [...prev, {
                id: Date.now() + 1,
                role: 'assistant',
                content: errorMessage,
                created_at: new Date().toISOString(),
            }]);
        } finally {
            setSending(false);
        }
    };

    const handleFileSelect = (e) => {
        const files = Array.from(e.target.files);
        const validFiles = files.filter(file => {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf', 
                                 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                 'text/plain'];
            
            if (file.size > maxSize) {
                alert(`File ${file.name} is too large. Maximum size is 10MB.`);
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                alert(`File ${file.name} is not a supported type.`);
                return false;
            }
            
            return true;
        });
        
        setSelectedFiles(prev => [...prev, ...validFiles]);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const removeFile = (index) => {
        setSelectedFiles(prev => prev.filter((_, i) => i !== index));
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
                                onClick={showInsightsView}
                                className="px-3 py-2 rounded-xl bg-white/70 hover:bg-white text-teal-600 border border-mint-200/60 text-sm font-semibold transition-all shadow-sm"
                                title="View insights"
                            >
                                Insights
                            </button>
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
                                <h3 className="text-xl font-semibold text-gray-700 mb-2">
                                    Hi! I'm Addy
                                </h3>
                                <p className="text-gray-500 mb-6 max-w-md">
                                    I'm your business COO. Ask me anything about your finances, sales, team, or inventory. You can also attach documents, receipts, invoices, quotes, contracts, notes, or any relevant files - I'll extract the information and reference historical data to help you.
                                </p>

                                <div className="grid grid-cols-2 gap-3">
                                    <button
                                        onClick={() => sendMessage('What is my cash position?')}
                                        className="px-4 py-3 bg-white/70 backdrop-blur-sm border border-mint-200/50 text-teal-700 rounded-xl hover:bg-mint-50/80 hover:border-mint-300/70 transition-all shadow-sm text-sm font-medium"
                                    >
                                        Cash Position
                                    </button>
                                    <button
                                        onClick={() => sendMessage('Show me top expenses')}
                                        className="px-4 py-3 bg-white/70 backdrop-blur-sm border border-mint-200/50 text-teal-700 rounded-xl hover:bg-mint-50/80 hover:border-mint-300/70 transition-all shadow-sm text-sm font-medium"
                                    >
                                        Top Expenses
                                    </button>
                                    <button
                                        onClick={() => sendMessage('What should I focus on today?')}
                                        className="px-4 py-3 bg-white/70 backdrop-blur-sm border border-mint-200/50 text-teal-700 rounded-xl hover:bg-mint-50/80 hover:border-mint-300/70 transition-all shadow-sm text-sm font-medium"
                                    >
                                        Daily Focus
                                    </button>
                                    <button
                                        onClick={() => sendMessage('Show me overdue invoices')}
                                        className="px-4 py-3 bg-white/70 backdrop-blur-sm border border-mint-200/50 text-teal-700 rounded-xl hover:bg-mint-50/80 hover:border-mint-300/70 transition-all shadow-sm text-sm font-medium"
                                    >
                                        Overdue Invoices
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
                                                {message.role === 'assistant' ? (
                                                    <div className="prose prose-sm max-w-none">
                                                        <ReactMarkdown
                                                            components={{
                                                                p: ({ children }) => <p className="mb-2 last:mb-0">{children}</p>,
                                                                strong: ({ children }) => <strong className="font-semibold text-inherit">{children}</strong>,
                                                                em: ({ children }) => <em className="italic">{children}</em>,
                                                                a: ({ href, children }) => (
                                                                    <a 
                                                                        href={href} 
                                                                        className="text-teal-600 hover:text-teal-700 underline"
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                    >
                                                                        {children}
                                                                    </a>
                                                                ),
                                                                ul: ({ children }) => <ul className="list-disc list-inside mb-2 space-y-1">{children}</ul>,
                                                                ol: ({ children }) => <ol className="list-decimal list-inside mb-2 space-y-1">{children}</ol>,
                                                                li: ({ children }) => <li className="ml-2">{children}</li>,
                                                                code: ({ children }) => (
                                                                    <code className="bg-gray-100 px-1.5 py-0.5 rounded text-sm font-mono">
                                                                        {children}
                                                                    </code>
                                                                ),
                                                                blockquote: ({ children }) => (
                                                                    <blockquote className="border-l-4 border-teal-300 pl-3 italic my-2">
                                                                        {children}
                                                                    </blockquote>
                                                                ),
                                                            }}
                                                        >
                                                            {message.content}
                                                        </ReactMarkdown>
                                                    </div>
                                                ) : (
                                                    <div className="whitespace-pre-wrap">{message.content}</div>
                                                )}
                                                
                                                {/* Display attachments */}
                                                {message.attachments && message.attachments.length > 0 && (
                                                    <div className="mt-3 space-y-2">
                                                        {message.attachments.map((attachment, idx) => (
                                                            <div key={idx} className={`flex items-center gap-2 p-2 rounded-lg ${
                                                                message.role === 'user' 
                                                                    ? 'bg-white/20' 
                                                                    : 'bg-mint-50/50'
                                                            }`}>
                                                                {attachment.mime_type?.startsWith('image/') ? (
                                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                    </svg>
                                                                ) : (
                                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                )}
                                                                <span className="text-sm truncate flex-1">{attachment.file_name}</span>
                                                                {attachment.file_size && (
                                                                    <span className="text-xs opacity-75">
                                                                        {(attachment.file_size / 1024).toFixed(1)} KB
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>

                                            {/* Action Confirmation */}
                                            {message.role === 'assistant' && message.metadata?.action && (
                                                <ActionConfirmation
                                                    action={message.metadata.action}
                                                    messageId={message.id}
                                                    onConfirm={(result) => {
                                                        // Update the message in local state with the result
                                                        setMessages(prev => prev.map(msg => 
                                                            msg.id === message.id 
                                                                ? { ...msg, action_result: result, action_executed: true }
                                                                : msg
                                                        ));
                                                    }}
                                                    onCancel={() => {
                                                        // Just reload to get fresh state
                                                        loadHistory();
                                                    }}
                                                    onUpdateMessage={(msgId, updates) => {
                                                        // Update message in local state
                                                        setMessages(prev => prev.map(msg => 
                                                            msg.id === msgId 
                                                                ? { ...msg, ...updates }
                                                                : msg
                                                        ));
                                                    }}
                                                />
                                            )}
                                            
                                            {/* Quick actions */}
                                            {message.role === 'assistant' && message.metadata?.quick_actions && (
                                                <div className="flex flex-wrap gap-2 mt-2">
                                                    {message.metadata.quick_actions.map((action, idx) => (
                                                        <button
                                                            key={idx}
                                                            onClick={() => handleQuickAction(action)}
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
                        {/* Selected Files Preview */}
                        {selectedFiles.length > 0 && (
                            <div className="mb-3 space-y-2">
                                {selectedFiles.map((file, index) => (
                                    <div key={index} className="flex items-center gap-2 p-2 bg-white/60 backdrop-blur-sm border border-mint-200/50 rounded-lg">
                                        {file.type.startsWith('image/') ? (
                                            <svg className="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        ) : (
                                            <svg className="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        )}
                                        <span className="text-sm text-gray-700 flex-1 truncate">{file.name}</span>
                                        <span className="text-xs text-gray-500">{(file.size / 1024).toFixed(1)} KB</span>
                                        <button
                                            onClick={() => removeFile(index)}
                                            className="p-1 text-teal-600 hover:text-teal-700 rounded"
                                        >
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="flex gap-2">
                            <input
                                ref={fileInputRef}
                                type="file"
                                multiple
                                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                                onChange={handleFileSelect}
                                className="hidden"
                            />
                            <button
                                onClick={() => fileInputRef.current?.click()}
                                disabled={sending}
                                className="px-4 py-3 bg-white/80 backdrop-blur-sm border border-mint-200/50 rounded-xl hover:bg-white/90 hover:border-teal-300/70 transition-all shadow-sm text-teal-600 disabled:opacity-50"
                                title="Attach file"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                            </button>
                            <textarea
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                onKeyPress={handleKeyPress}
                                placeholder="Ask me anything or attach documents, receipts, invoices, quotes, contracts, notes, or any relevant files..."
                                rows="1"
                                className="flex-1 px-4 py-3 bg-white/80 backdrop-blur-sm border border-mint-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 resize-none text-gray-900 placeholder:text-gray-400 shadow-sm"
                                disabled={sending}
                            />
                            <button
                                onClick={() => sendMessage()}
                                disabled={(!input.trim() && selectedFiles.length === 0) || sending}
                                className="px-6 py-3 bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-xl hover:from-teal-600 hover:to-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>

                        <p className="text-xs text-teal-600/60 mt-2">
                            Press Enter to send, Shift+Enter for new line • Attach any relevant documents, images, or files. I can extract information and reference historical data.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
