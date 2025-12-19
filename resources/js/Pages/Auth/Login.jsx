import { Head, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Login({ errors }) {
    // Auto-redirect to Penda Cloud SSO
    useEffect(() => {
        router.visit('/auth/penda', { method: 'get' });
    }, []);

    return (
        <>
            <Head title="Login - Addy" />
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
                                Welcome to Addy
                            </h2>
                            <p className="mt-2 text-sm text-teal-600/70">
                                Sign in with your Penda Cloud account to continue
                            </p>
                        </div>

                        {/* Error Message */}
                        {errors?.sso && (
                            <div className="bg-red-50 border-2 border-red-300 text-red-700 px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-2">
                                <svg className="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                </svg>
                                <span>{errors.sso}</span>
                            </div>
                        )}

                        {/* Loading State */}
                        <div className="text-center space-y-4">
                            <div className="flex justify-center">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
                            </div>
                            <p className="text-teal-600">Redirecting to Penda Cloud...</p>
                        </div>

                        {/* Manual Redirect Button (if auto-redirect fails) */}
                        <div className="pt-4">
                            <a
                                href="/auth/penda"
                                className="w-full flex items-center justify-center gap-3 px-4 py-3 bg-gradient-to-r from-[#4e4af9] to-[#56ce85] rounded-xl hover:opacity-90 transition-opacity text-white font-medium shadow-lg"
                            >
                                <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" fill="white" fillOpacity="0.2"/>
                                    <path d="M12 6v12M6 12h12" stroke="white" strokeWidth="2" strokeLinecap="round"/>
                                </svg>
                                Sign in with Penda Cloud
                            </a>
                        </div>
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
