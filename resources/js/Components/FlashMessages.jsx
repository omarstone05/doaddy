import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { CheckCircle, XCircle, Info, AlertTriangle, X } from 'lucide-react';

export default function FlashMessages() {
    const { flash } = usePage().props;
    const [messages, setMessages] = useState([]);

    useEffect(() => {
        const newMessages = [];
        
        if (flash?.message) {
            newMessages.push({
                id: Date.now(),
                type: 'success',
                message: flash.message,
            });
        }
        
        if (flash?.success) {
            newMessages.push({
                id: Date.now() + 1,
                type: 'success',
                message: flash.success,
            });
        }
        
        if (flash?.error) {
            newMessages.push({
                id: Date.now() + 2,
                type: 'error',
                message: flash.error,
            });
        }

        if (newMessages.length > 0) {
            setMessages(newMessages);
            
            // Auto-dismiss after 5 seconds
            const timer = setTimeout(() => {
                setMessages([]);
            }, 5000);

            return () => clearTimeout(timer);
        }
    }, [flash]);

    const removeMessage = (id) => {
        setMessages(messages.filter(msg => msg.id !== id));
    };

    if (messages.length === 0) {
        return null;
    }

    return (
        <div className="fixed top-20 right-4 z-50 space-y-2 max-w-md">
            {messages.map((msg) => {
                const isSuccess = msg.type === 'success';
                const isError = msg.type === 'error';
                
                return (
                    <div
                        key={msg.id}
                        className={`
                            flex items-start gap-3 p-4 rounded-lg shadow-lg border
                            ${isSuccess ? 'bg-green-50 border-green-200' : ''}
                            ${isError ? 'bg-red-50 border-red-200' : ''}
                            animate-in slide-in-from-right-5 duration-300
                        `}
                    >
                        <div className="flex-shrink-0 mt-0.5">
                            {isSuccess && <CheckCircle className="h-5 w-5 text-green-600" />}
                            {isError && <XCircle className="h-5 w-5 text-red-600" />}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className={`text-sm font-medium ${isSuccess ? 'text-green-800' : 'text-red-800'}`}>
                                {msg.message}
                            </p>
                        </div>
                        <button
                            onClick={() => removeMessage(msg.id)}
                            className="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}

