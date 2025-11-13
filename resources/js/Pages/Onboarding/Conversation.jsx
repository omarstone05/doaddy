import { useState, useEffect, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/Button';

export default function OnboardingConversation({ user, organization }) {
    const [currentStep, setCurrentStep] = useState(0);
    const [formData, setFormData] = useState({
        name: user?.name || '',
        business_name: organization?.name || '',
        industry: organization?.industry || '',
        currency: organization?.currency || 'ZMW',
        tone_preference: organization?.tone_preference || 'professional',
    });
    const [inputValue, setInputValue] = useState('');
    const [messages, setMessages] = useState([]);
    const [isTyping, setIsTyping] = useState(true);
    const messagesEndRef = useRef(null);
    const inputRef = useRef(null);

const onboardingSteps = [
    {
        id: 'welcome',
        addy: "Hi! 👋 I'm Addy, your AI Business COO. I'm here to help you manage your business smarter. Let's get started!",
        type: 'message',
    },
    {
        id: 'name',
        addy: "First things first - what should I call you?",
        type: 'input',
        field: 'name',
        placeholder: 'Your name',
    },
    {
        id: 'business',
        addy: "Great to meet you, {name}! What's the name of your business?",
        type: 'input',
        field: 'business_name',
        placeholder: 'Business name',
    },
    {
        id: 'industry',
        addy: "Nice! What industry are you in?",
        type: 'select',
        field: 'industry',
        options: [
            { value: 'retail', label: 'Retail' },
            { value: 'services', label: 'Services' },
            { value: 'manufacturing', label: 'Manufacturing' },
            { value: 'technology', label: 'Technology' },
            { value: 'healthcare', label: 'Healthcare' },
            { value: 'other', label: 'Other' },
        ],
    },
    {
        id: 'currency',
        addy: "What currency do you use for your business?",
        type: 'select',
        field: 'currency',
        options: [
            { value: 'ZMW', label: 'ZMW (Zambian Kwacha)' },
            { value: 'USD', label: 'USD (US Dollar)' },
            { value: 'EUR', label: 'EUR (Euro)' },
            { value: 'GBP', label: 'GBP (British Pound)' },
        ],
    },
    {
        id: 'tone',
        addy: "How would you like me to communicate with you?",
        type: 'select',
        field: 'tone_preference',
        options: [
            { value: 'professional', label: 'Professional - Clear and structured' },
            { value: 'casual', label: 'Casual - Light and approachable' },
            { value: 'motivational', label: 'Motivational - Pep talks and encouragement' },
            { value: 'sassy', label: 'Sassy - Bold with personality' },
            { value: 'technical', label: 'Technical - Detailed and precise' },
        ],
    },
    {
        id: 'complete',
        addy: "Perfect! I've got everything I need. You're all set! Let me take you to your dashboard where we can start managing your business together. 🚀",
        type: 'complete',
    },
];

    useEffect(() => {
        // Start with welcome message
        setTimeout(() => {
            addMessage('addy', onboardingSteps[0].addy);
            setIsTyping(false);
        }, 500);
    }, []);

    useEffect(() => {
        scrollToBottom();
        if (currentStep >= 0 && currentStep < onboardingSteps.length) {
            const step = onboardingSteps[currentStep];
            
            // Skip if this is the initial welcome message (already handled in first useEffect)
            if (currentStep === 0) {
                return;
            }
            
            if (step.type === 'input' || step.type === 'select') {
                // Pre-fill input with existing data
                if (step.type === 'input' && formData[step.field]) {
                    setInputValue(formData[step.field]);
                }
                setTimeout(() => {
                    setIsTyping(true);
                    setTimeout(() => {
                        const message = step.addy.replace('{name}', formData.name || 'there');
                        addMessage('addy', message);
                        setIsTyping(false);
                        if (inputRef.current && step.type === 'input') {
                            inputRef.current.focus();
                        }
                    }, 800);
                }, 300);
            } else if (step.type === 'complete') {
                // Show complete message
                setIsTyping(true);
                setTimeout(() => {
                    const message = step.addy.replace('{name}', formData.name || 'there');
                    addMessage('addy', message);
                    setIsTyping(false);
                }, 800);
            }
        }
    }, [currentStep]);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const addMessage = (role, content) => {
        setMessages(prev => [...prev, { role, content, timestamp: Date.now() }]);
    };

    const handleNext = () => {
        const step = onboardingSteps[currentStep];
        
        // If we're on complete step, submit the form
        if (step.type === 'complete') {
            // Complete onboarding
            router.post('/onboarding/complete', formData, {
                onSuccess: () => {
                    router.visit('/dashboard');
                },
                onError: (errors) => {
                    console.error('Onboarding completion error:', errors);
                    alert('There was an error completing onboarding. Please try again.');
                },
            });
            return;
        }
        
        if (step.type === 'input' || step.type === 'select') {
            if (!formData[step.field]) {
                return; // Don't proceed if field is empty
            }
        }

        // Add user response to messages (only for input, select already handled in handleSelectChange)
        if (step.type === 'input') {
            const userMessage = formData[step.field];
            addMessage('user', userMessage);
        }

        // Move to next step
        const nextStepIndex = currentStep + 1;
        if (nextStepIndex < onboardingSteps.length) {
            setCurrentStep(nextStepIndex);
            // Pre-fill next input if we have data
            const nextStep = onboardingSteps[nextStepIndex];
            if (nextStep && nextStep.type === 'input') {
                setInputValue(formData[nextStep.field] || '');
            } else {
                setInputValue('');
            }
        }
    };

    const handleInputChange = (value) => {
        setInputValue(value);
        const step = onboardingSteps[currentStep];
        setFormData(prev => ({ ...prev, [step.field]: value }));
    };

    const handleSelectChange = (value) => {
        const step = onboardingSteps[currentStep];
        setFormData(prev => ({ ...prev, [step.field]: value }));
        
        // Add user response to messages immediately
        addMessage('user', value);
        
        // Auto-advance after selection - use handleNext to ensure proper flow
        setTimeout(() => {
            handleNext();
        }, 500);
    };

    const currentStepData = onboardingSteps[currentStep];
    const showInput = currentStepData?.type === 'input' && !isTyping;
    const showSelect = currentStepData?.type === 'select' && !isTyping;
    const showButton = currentStepData?.type === 'message' && !isTyping;
    // Show complete button when on complete step, not typing, and complete message has been shown
    const completeMessageShown = messages.some(m => m.role === 'addy' && m.content.includes("Perfect!"));
    const showComplete = currentStepData?.type === 'complete' && !isTyping && completeMessageShown;
    const canProceed = currentStepData?.type === 'input' ? formData[currentStepData.field] : true;

    return (
        <>
            <Head title="Welcome to Addy" />
            <div className="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
                {/* Animated Gradient Background */}
                <div className="fixed inset-0 bg-gradient-to-br from-teal-400 via-mint-300 to-teal-500 animate-gradient">
                    <div className="absolute inset-0 bg-gradient-to-tr from-teal-500/20 via-transparent to-mint-400/20 animate-gradient-reverse"></div>
                </div>

                {/* Glass Container */}
                <div className="relative z-10 w-full max-w-2xl">
                    <div className="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 p-8 md:p-12">
                        {/* Header */}
                        <div className="flex items-center gap-4 mb-8 pb-6 border-b border-teal-200/50">
                            <div className="w-16 h-16 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 shadow-lg flex items-center justify-center border-4 border-white/50">
                                <img 
                                    src="/assets/logos/icon.png" 
                                    alt="Addy" 
                                    className="w-10 h-10 object-contain"
                                />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-teal-700">Addy</h1>
                                <p className="text-sm text-teal-600/70">Your AI Business COO</p>
                            </div>
                        </div>

                        {/* Messages */}
                        <div className="space-y-4 mb-6 max-h-[400px] overflow-y-auto pr-2">
                            {messages.map((message, index) => (
                                <div
                                    key={index}
                                    className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                                >
                                    <div
                                        className={`max-w-[80%] rounded-2xl px-4 py-3 ${
                                            message.role === 'user'
                                                ? 'bg-gradient-to-br from-teal-500 to-teal-600 text-white'
                                                : 'bg-white/70 backdrop-blur-sm text-gray-800 border border-teal-200/50'
                                        }`}
                                    >
                                        <p className="whitespace-pre-wrap">{message.content}</p>
                                    </div>
                                </div>
                            ))}

                            {isTyping && (
                                <div className="flex justify-start">
                                    <div className="bg-white/70 backdrop-blur-sm rounded-2xl px-4 py-3 border border-teal-200/50">
                                        <div className="flex space-x-2">
                                            <div className="w-2 h-2 bg-teal-500 rounded-full animate-bounce"></div>
                                            <div className="w-2 h-2 bg-teal-500 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
                                            <div className="w-2 h-2 bg-teal-500 rounded-full animate-bounce" style={{ animationDelay: '0.4s' }}></div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div ref={messagesEndRef} />
                        </div>

                        {/* Input Area */}
                        {showInput && (
                            <div className="space-y-4">
                                <input
                                    ref={inputRef}
                                    type="text"
                                    value={inputValue}
                                    onChange={(e) => handleInputChange(e.target.value)}
                                    onKeyPress={(e) => {
                                        if (e.key === 'Enter' && canProceed) {
                                            handleNext();
                                        }
                                    }}
                                    placeholder={currentStepData.placeholder}
                                    className="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-teal-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 text-gray-900 placeholder:text-gray-400"
                                    autoFocus
                                />
                                <Button
                                    onClick={handleNext}
                                    disabled={!canProceed}
                                    className="w-full"
                                >
                                    Continue
                                </Button>
                            </div>
                        )}

                        {showSelect && (
                            <div className="space-y-4">
                                <div className="grid gap-2">
                                    {currentStepData.options.map((option) => (
                                        <button
                                            key={option.value}
                                            onClick={() => handleSelectChange(option.value)}
                                            className={`px-4 py-3 rounded-xl text-left transition-all ${
                                                formData[currentStepData.field] === option.value
                                                    ? 'bg-gradient-to-br from-teal-500 to-teal-600 text-white shadow-lg'
                                                    : 'bg-white/80 backdrop-blur-sm border border-teal-200/50 text-gray-800 hover:bg-white/90 hover:border-teal-300/70'
                                            }`}
                                        >
                                            {option.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {showButton && (
                            <Button
                                onClick={handleNext}
                                className="w-full"
                            >
                                Let's get started!
                            </Button>
                        )}

                        {showComplete && (
                            <div className="space-y-4">
                                <Button
                                    onClick={handleNext}
                                    className="w-full bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700"
                                >
                                    Go to Dashboard 🚀
                                </Button>
                                <p className="text-center text-sm text-teal-600/70">
                                    You can always chat with Addy from anywhere in the app!
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                <style>{`
                    @keyframes gradient {
                        0%, 100% {
                            background-position: 0% 50%;
                        }
                        50% {
                            background-position: 100% 50%;
                        }
                    }
                    @keyframes gradient-reverse {
                        0%, 100% {
                            background-position: 100% 50%;
                        }
                        50% {
                            background-position: 0% 50%;
                        }
                    }
                    .animate-gradient {
                        background-size: 200% 200%;
                        animation: gradient 15s ease infinite;
                    }
                    .animate-gradient-reverse {
                        background-size: 200% 200%;
                        animation: gradient-reverse 20s ease infinite;
                    }
                `}</style>
            </div>
        </>
    );
}
