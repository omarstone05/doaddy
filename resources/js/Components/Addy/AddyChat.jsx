import React, { useState, useEffect, useRef } from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import { router } from '@inertiajs/react';
import ActionConfirmation from './ActionConfirmation';
import ReactMarkdown from 'react-markdown';
import UploadModal from '../UploadModal';
import { X, Trash2, Lightbulb, Paperclip, Send, Image, FileText } from 'lucide-react';

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
    const [showUploadModal, setShowUploadModal] = useState(false);
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
            const response = await window.axios.get('/api/addy/chat/history');
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

            const response = await window.axios.post('/api/addy/chat', formData, {
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
            await window.axios.delete('/api/addy/chat/history');
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
                className="fixed inset-0 bg-black/30 backdrop-blur-sm z-50"
                onClick={closeAddy}
            />

            {/* Chat Container */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div className="bg-white rounded-3xl shadow-2xl w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden">

                    {/* Header */}
                    <div className="bg-white border-b border-gray-100 p-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-sm">
                                <img
                                    src="/assets/logos/icon-white.png"
                                    alt="Addy"
                                    className="w-6 h-6 object-contain"
                                />
                            </div>
                            <div>
                                <h2 className="font-bold text-lg text-gray-900">Addy</h2>
                                <p className="text-xs text-gray-500">Your Business COO</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                onClick={showInsightsView}
                                className="px-4 py-2 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-600 text-sm font-semibold transition-colors"
                                title="View insights"
                            >
                                <Lightbulb className="w-4 h-4 inline-block mr-1.5" />
                                Insights
                            </button>
                            <button
                                onClick={clearHistory}
                                className="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors"
                                title="Clear history"
                            >
                                <Trash2 className="w-5 h-5" />
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

                    {/* Messages */}
                    <div className="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50/50">
                        {loading ? (
                            <div className="flex items-center justify-center h-full">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500 border-t-transparent"></div>
                            </div>
                        ) : messages.length === 0 ? (
                            <div className="flex flex-col items-center justify-center h-full text-center">
                                <div className="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center mb-4">
                                    <img
                                        src="/assets/logos/icon.png"
                                        alt="Addy"
                                        className="w-10 h-10 object-contain"
                                    />
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-2">
                                    Hi! I'm Addy
                                </h3>
                                <p className="text-gray-500 mb-6 max-w-md">
                                    I'm your business COO. Ask me anything about your finances, sales, team, or inventory. You can also attach documents, receipts, invoices, quotes, contracts, notes, or any relevant files.
                                </p>

                                <div className="grid grid-cols-2 gap-3 max-w-md w-full">
                                    <button
                                        onClick={() => sendMessage('What is my cash position?')}
                                        className="px-4 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-teal-200 transition-all text-sm font-medium"
                                    >
                                        💰 Cash Position
                                    </button>
                                    <button
                                        onClick={() => sendMessage('Show me top expenses')}
                                        className="px-4 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-teal-200 transition-all text-sm font-medium"
                                    >
                                        📊 Top Expenses
                                    </button>
                                    <button
                                        onClick={() => sendMessage('What should I focus on today?')}
                                        className="px-4 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-teal-200 transition-all text-sm font-medium"
                                    >
                                        🎯 Daily Focus
                                    </button>
                                    <button
                                        onClick={() => sendMessage('Show me overdue invoices')}
                                        className="px-4 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-teal-200 transition-all text-sm font-medium"
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
                                                className={`rounded-2xl px-4 py-3 ${message.role === 'user'
                                                    ? 'bg-teal-600 text-white'
                                                    : 'bg-white text-gray-900 border border-gray-200 shadow-sm'
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
                                                            <div key={idx} className={`flex items-center gap-2 p-2 rounded-lg ${message.role === 'user'
                                                                ? 'bg-white/20'
                                                                : 'bg-gray-50'
                                                                }`}>
                                                                {attachment.mime_type?.startsWith('image/') ? (
                                                                    <Image className="w-5 h-5" />
                                                                ) : (
                                                                    <FileText className="w-5 h-5" />
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
                                                            className="px-3 py-1.5 bg-white border border-gray-200 rounded-full text-xs text-gray-700 hover:bg-gray-50 hover:border-teal-200 transition-all"
                                                        >
                                                            {action.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                        </div>

                                        {/* Avatar */}
                                        {message.role === 'assistant' && (
                                            <div className="w-8 h-8 rounded-xl bg-teal-50 flex items-center justify-center mr-2 flex-shrink-0">
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
                                        <div className="w-8 h-8 rounded-xl bg-teal-50 flex items-center justify-center mr-2">
                                            <img
                                                src="/assets/logos/icon.png"
                                                alt="Addy"
                                                className="w-5 h-5 object-contain"
                                            />
                                        </div>
                                        <div className="bg-white border border-gray-200 rounded-2xl px-4 py-3 shadow-sm">
                                            <div className="flex space-x-2">
                                                <div className="w-2 h-2 bg-teal-500 rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></div>
                                                <div className="w-2 h-2 bg-teal-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></div>
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
                    <div className="border-t border-gray-100 bg-white p-4">
                        {/* Selected Files Preview */}
                        {selectedFiles.length > 0 && (
                            <div className="mb-3 space-y-2">
                                {selectedFiles.map((file, index) => (
                                    <div key={index} className="flex items-center gap-2 p-2 bg-gray-50 border border-gray-200 rounded-lg">
                                        {file.type.startsWith('image/') ? (
                                            <Image className="w-5 h-5 text-teal-600" />
                                        ) : (
                                            <FileText className="w-5 h-5 text-teal-600" />
                                        )}
                                        <span className="text-sm text-gray-700 flex-1 truncate">{file.name}</span>
                                        <span className="text-xs text-gray-500">{(file.size / 1024).toFixed(1)} KB</span>
                                        <button
                                            onClick={() => removeFile(index)}
                                            className="p-1 text-gray-400 hover:text-gray-600 rounded"
                                        >
                                            <X className="w-4 h-4" />
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
                                onClick={() => setShowUploadModal(true)}
                                disabled={sending}
                                className="px-4 py-3 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors text-gray-600 disabled:opacity-50"
                                title="Attach file"
                            >
                                <Paperclip className="w-5 h-5" />
                            </button>
                            <textarea
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                onKeyPress={handleKeyPress}
                                placeholder="Ask me anything..."
                                rows="1"
                                className="flex-1 px-4 py-3 bg-gray-100 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/30 resize-none text-gray-900 placeholder:text-gray-400"
                                disabled={sending}
                            />
                            <button
                                onClick={() => sendMessage()}
                                disabled={(!input.trim() && selectedFiles.length === 0) || sending}
                                className="px-6 py-3 bg-teal-600 text-white rounded-xl hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <Send className="w-5 h-5" />
                            </button>
                        </div>

                        <p className="text-xs text-gray-400 mt-2 text-center">
                            Press Enter to send • Shift+Enter for new line
                        </p>
                    </div>
                </div>
            </div>

            {/* Upload Modal */}
            <UploadModal
                isOpen={showUploadModal}
                onClose={() => setShowUploadModal(false)}
                onSuccess={(results) => {
                    console.log('Upload successful:', results);
                    // Optionally send a message to Addy about the upload
                    if (results.length > 0) {
                        sendMessage(`I just uploaded ${results.length} file(s). Please process them.`);
                    }
                }}
                context="chat"
            />
        </>
    );
}
