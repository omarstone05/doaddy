import { Head, Link, useForm, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/Button';
import { useState } from 'react';
import axios from 'axios';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    // Tab state
    const [activeTab, setActiveTab] = useState('email'); // 'email' or 'whatsapp'

    // WhatsApp login state
    const [whatsappPhone, setWhatsappPhone] = useState('');
    const [whatsappCode, setWhatsappCode] = useState('');
    const [sendingCode, setSendingCode] = useState(false);
    const [verifyingCode, setVerifyingCode] = useState(false);
    const [codeSent, setCodeSent] = useState(false);
    const [whatsappMessage, setWhatsappMessage] = useState('');
    const [whatsappError, setWhatsappError] = useState('');

    // Get CSRF token
    const getCsrfToken = () => {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        return token ? token.content : '';
    };

    // Send WhatsApp verification code
    const sendWhatsAppCode = async (e) => {
        e.preventDefault();
        setSendingCode(true);
        setWhatsappError('');
        setWhatsappMessage('');

        try {
            const response = await axios.post('/login/whatsapp/send-code', {
                phone_number: whatsappPhone,
            }, {
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            if (response.data.success) {
                setCodeSent(true);
                setWhatsappMessage(response.data.message);
            } else {
                setWhatsappError(response.data.message || 'Failed to send verification code');
            }
        } catch (error) {
            const errorMessage = error.response?.data?.message || 'Failed to send verification code. Please try again.';
            setWhatsappError(errorMessage);
        } finally {
            setSendingCode(false);
        }
    };

    // Verify WhatsApp code and login
    const verifyWhatsAppCode = async (e) => {
        e.preventDefault();
        setVerifyingCode(true);
        setWhatsappError('');

        try {
            const response = await axios.post('/login/whatsapp/verify', {
                phone_number: whatsappPhone,
                code: whatsappCode,
            }, {
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            if (response.data.success) {
                // Redirect to dashboard
                router.visit(response.data.redirect || '/dashboard');
            } else {
                setWhatsappError(response.data.message || 'Invalid verification code');
            }
        } catch (error) {
            const errorMessage = error.response?.data?.message || 'Invalid verification code. Please try again.';
            setWhatsappError(errorMessage);
        } finally {
            setVerifyingCode(false);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post('/login', {
            onError: (errors) => {
                // Handle 419 CSRF token mismatch
                if (errors.message && errors.message.includes('419')) {
                    alert('Your session has expired. Please refresh the page and try again.');
                    window.location.reload();
                }
            },
        });
    };

    return (
        <>
            <Head title="Login" />
            <div className="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                {/* Animated Gradient Background */}
                <div className="fixed inset-0 bg-gradient-to-br from-teal-400 via-mint-300 to-teal-500 animate-gradient">
                    <div className="absolute inset-0 bg-gradient-to-tr from-teal-500/20 via-transparent to-mint-400/20 animate-gradient-reverse"></div>
                </div>

                {/* Glass Container */}
                <div className="relative z-10 max-w-md w-full">
                    <div className="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 p-8 space-y-8">
                        <div className="text-center">
                            <div className="flex justify-center mb-4">
                                <div className="w-16 h-16 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 shadow-lg flex items-center justify-center border-4 border-white/50">
                                    <img 
                                        src="/assets/logos/icon.png" 
                                        alt="Addy" 
                                        className="w-10 h-10 object-contain"
                                    />
                                </div>
                            </div>
                            <h2 className="text-3xl font-bold text-teal-700">
                                Sign in to Addy
                            </h2>
                            <p className="mt-2 text-sm text-teal-600/70">
                                Or{' '}
                                <Link href="/register" className="font-medium text-teal-600 hover:text-teal-700 underline">
                                    create a new account
                                </Link>
                            </p>
                        </div>

                        {/* Tabs */}
                        <div className="flex space-x-1 bg-teal-50/50 p-1 rounded-xl">
                            <button
                                type="button"
                                onClick={() => {
                                    setActiveTab('email');
                                    setWhatsappError('');
                                    setWhatsappMessage('');
                                }}
                                className={`flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition-all ${
                                    activeTab === 'email'
                                        ? 'bg-white text-teal-700 shadow-sm'
                                        : 'text-teal-600 hover:text-teal-700'
                                }`}
                            >
                                Email Login
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setActiveTab('whatsapp');
                                    setWhatsappError('');
                                    setWhatsappMessage('');
                                }}
                                className={`flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition-all ${
                                    activeTab === 'whatsapp'
                                        ? 'bg-white text-teal-700 shadow-sm'
                                        : 'text-teal-600 hover:text-teal-700'
                                }`}
                            >
                                WhatsApp Login
                            </button>
                        </div>

                        {/* Email/Password Login Tab */}
                        {activeTab === 'email' && (

                        <form className="space-y-6" onSubmit={submit}>
                            <div className="space-y-4">
                                <div>
                                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                                        Email address
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-teal-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 text-gray-900 placeholder:text-gray-400"
                                        placeholder="you@example.com"
                                        required
                                    />
                                    {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                                </div>
                                <div>
                                    <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
                                        Password
                                    </label>
                                    <input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        className="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-teal-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 text-gray-900 placeholder:text-gray-400"
                                        placeholder="••••••••"
                                        required
                                    />
                                    {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                                </div>
                                <div className="flex items-center">
                                    <input
                                        id="remember"
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="h-4 w-4 text-teal-500 focus:ring-teal-500 border-teal-300 rounded"
                                    />
                                    <label htmlFor="remember" className="ml-2 block text-sm text-gray-700">
                                        Remember me
                                    </label>
                                </div>
                            </div>
                            <div>
                                <Button type="submit" className="w-full" disabled={processing}>
                                    Sign in
                                </Button>
                            </div>
                        </form>
                        )}

                        {/* WhatsApp Login Tab */}
                        {activeTab === 'whatsapp' && (
                            <>
                            {!codeSent ? (
                                <form className="space-y-6" onSubmit={sendWhatsAppCode}>
                                    <div>
                                        <label htmlFor="whatsapp-phone" className="block text-sm font-medium text-gray-700 mb-1">
                                            Phone Number
                                        </label>
                                        <input
                                            id="whatsapp-phone"
                                            type="tel"
                                            value={whatsappPhone}
                                            onChange={(e) => setWhatsappPhone(e.target.value)}
                                            className="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-teal-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 text-gray-900 placeholder:text-gray-400"
                                            placeholder="0*********"
                                            required
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Enter your phone number (e.g., 0*********)</p>
                                    </div>
                                    
                                    {whatsappError && (
                                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                                            {whatsappError}
                                        </div>
                                    )}

                                    <Button 
                                        type="submit" 
                                        className="w-full bg-green-600 hover:bg-green-700" 
                                        disabled={sendingCode}
                                    >
                                        {sendingCode ? (
                                            <span className="flex items-center justify-center">
                                                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Sending Code...
                                            </span>
                                        ) : (
                                            'Send Verification Code'
                                        )}
                                    </Button>
                                </form>
                            ) : (
                                <form className="space-y-6" onSubmit={verifyWhatsAppCode}>
                                    {whatsappMessage && (
                                        <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                                            {whatsappMessage}
                                        </div>
                                    )}

                                    <div>
                                        <label htmlFor="whatsapp-code" className="block text-sm font-medium text-gray-700 mb-1">
                                            Enter Verification Code
                                        </label>
                                        <input
                                            id="whatsapp-code"
                                            type="text"
                                            value={whatsappCode}
                                            onChange={(e) => setWhatsappCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                                            className="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border-2 border-teal-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-400 text-gray-900 placeholder:text-gray-400 text-center text-2xl font-mono tracking-widest"
                                            placeholder="000000"
                                            maxLength="6"
                                            required
                                            autoFocus
                                        />
                                    </div>

                                    {whatsappError && (
                                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                                            {whatsappError}
                                        </div>
                                    )}

                                    <div className="flex gap-3">
                                        <Button 
                                            type="button"
                                            variant="outline"
                                            className="flex-1"
                                            onClick={() => {
                                                setCodeSent(false);
                                                setWhatsappCode('');
                                                setWhatsappError('');
                                                setWhatsappMessage('');
                                            }}
                                        >
                                            Change Number
                                        </Button>
                                        <Button 
                                            type="submit" 
                                            className="flex-1 bg-green-600 hover:bg-green-700" 
                                            disabled={verifyingCode || whatsappCode.length !== 6}
                                        >
                                            {verifyingCode ? (
                                                <span className="flex items-center justify-center">
                                                    <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Verifying...
                                                </span>
                                            ) : (
                                                'Verify & Login'
                                            )}
                                        </Button>
                                    </div>
                                </form>
                            )}
                            </>
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

