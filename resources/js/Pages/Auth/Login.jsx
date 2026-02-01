import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ArrowRight, Shield, ExternalLink } from 'lucide-react';

export default function Login({ errors }) {
    const [isRedirecting, setIsRedirecting] = useState(false);
    const [autoRedirect, setAutoRedirect] = useState(!errors?.sso);
    
    // Build SSO URL with redirect parameter to ensure we return to this app
    const ssoUrl = `/auth/penda?redirect=${encodeURIComponent('/dashboard')}`;

    // Auto-redirect to Penda Cloud SSO (only if no errors)
    useEffect(() => {
        if (!errors?.sso && autoRedirect) {
            const timer = setTimeout(() => {
                setIsRedirecting(true);
                router.visit(ssoUrl, { method: 'get' });
            }, 1000);
            return () => clearTimeout(timer);
        }
    }, [errors, autoRedirect]);

    const handleLogin = () => {
        setIsRedirecting(true);
        router.visit(ssoUrl, { method: 'get' });
    };

    return (
        <>
            <Head title="Sign In - Addy" />
            <div className="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                {/* Animated Gradient Background */}
                <div className="fixed inset-0 bg-gradient-to-br from-teal-400 via-mint-300 to-teal-500 animate-gradient">
                    <div className="absolute inset-0 bg-gradient-to-tr from-teal-500/20 via-transparent to-mint-400/20 animate-gradient-reverse"></div>
                </div>

                {/* Glass Container */}
                <div className="relative z-10 max-w-md w-full">
                    <div className="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 p-8 space-y-6">
                        {/* Header */}
                        <div className="text-center">
                            <div className="flex justify-center mb-4">
                                <div className="w-16 h-16 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 shadow-lg flex items-center justify-center border-4 border-white/50">
                                    <img 
                                        src="/assets/logos/icon.png" 
                                        alt="Addy" 
                                        className="w-10 h-10 object-contain"
                                        onError={(e) => {
                                            e.target.style.display = 'none';
                                        }}
                                    />
                                </div>
                            </div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                Welcome back
                            </h1>
                            <p className="mt-2 text-gray-600">
                                Sign in to your Addy account
                            </p>
                        </div>

                        {/* Error Message */}
                        {errors?.sso && (
                            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-4 rounded-xl text-sm">
                                <div className="flex items-start gap-3">
                                    <svg className="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                    </svg>
                                    <div>
                                        <p className="font-medium">{errors.sso}</p>
                                        <p className="text-red-600/80 mt-1 text-xs">
                                            If this problem continues, please contact support.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Loading/Redirecting State */}
                        {(isRedirecting || (autoRedirect && !errors?.sso)) && (
                            <div className="text-center py-6 space-y-4">
                                <div className="flex justify-center">
                                    <div className="animate-spin rounded-full h-10 w-10 border-3 border-teal-200 border-t-teal-600"></div>
                                </div>
                                <div>
                                    <p className="text-teal-700 font-medium">Connecting to Penda Cloud...</p>
                                    <p className="text-gray-500 text-sm mt-1">You'll be redirected automatically</p>
                                </div>
                            </div>
                        )}

                        {/* SSO Info */}
                        {!isRedirecting && !autoRedirect && (
                            <div className="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <div className="flex items-start gap-3">
                                    <div className="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center flex-shrink-0">
                                        <Shield className="w-4 h-4 text-teal-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">Secure Sign-In</p>
                                        <p className="text-xs text-gray-500 mt-0.5">
                                            You'll sign in through Penda Cloud, our secure authentication system.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Sign In Button */}
                        <button
                            onClick={handleLogin}
                            disabled={isRedirecting}
                            className="w-full flex items-center justify-center gap-3 px-6 py-4 bg-gradient-to-r from-teal-500 to-teal-600 rounded-xl hover:from-teal-600 hover:to-teal-700 transition-all text-white font-semibold shadow-lg disabled:opacity-70 disabled:cursor-not-allowed"
                        >
                            {isRedirecting ? (
                                <>
                                    <div className="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
                                    <span>Connecting...</span>
                                </>
                            ) : (
                                <>
                                    <span>Sign in with Penda Cloud</span>
                                    <ArrowRight className="w-5 h-5" />
                                </>
                            )}
                        </button>

                        {/* Penda Cloud badge */}
                        <div className="flex items-center justify-center gap-2 text-xs text-gray-500">
                            <span>Powered by</span>
                            <a 
                                href="https://penda.cloud" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                className="flex items-center gap-1 font-medium text-gray-700 hover:text-teal-600 transition-colors"
                            >
                                <img 
                                    src="/logo/penda icon.svg" 
                                    alt="Penda" 
                                    className="w-4 h-4"
                                    onError={(e) => {
                                        e.target.style.display = 'none';
                                    }}
                                />
                                Penda Cloud
                                <ExternalLink className="w-3 h-3" />
                            </a>
                        </div>

                        {/* Divider */}
                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-gray-200"></div>
                            </div>
                            <div className="relative flex justify-center text-sm">
                                <span className="px-3 bg-white text-gray-500">New to Addy?</span>
                            </div>
                        </div>

                        {/* Register link */}
                        <div className="text-center">
                            <Link
                                href="/register"
                                className="inline-flex items-center gap-2 text-teal-600 hover:text-teal-700 font-medium transition-colors"
                            >
                                Create a free account
                                <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>

                        {/* Help links */}
                        <div className="flex items-center justify-center gap-4 pt-2 text-xs">
                            <Link
                                href="/forgot-password"
                                className="text-gray-500 hover:text-teal-600 transition-colors"
                            >
                                Forgot password?
                            </Link>
                            <span className="text-gray-300">|</span>
                            <a
                                href="mailto:support@penda.cloud"
                                className="text-gray-500 hover:text-teal-600 transition-colors"
                            >
                                Need help?
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
