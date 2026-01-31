import { Head, Link } from '@inertiajs/react';

export default function Forbidden() {
    return (
        <>
            <Head title="Access Denied - Addy" />
            <div className="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                {/* Animated Gradient Background */}
                <div className="fixed inset-0 bg-gradient-to-br from-teal-400 via-mint-300 to-teal-500 animate-gradient">
                    <div className="absolute inset-0 bg-gradient-to-tr from-teal-500/20 via-transparent to-mint-400/20 animate-gradient-reverse"></div>
                </div>

                {/* Glass Container */}
                <div className="relative z-10 max-w-lg w-full text-center">
                    <div className="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 p-10 space-y-6">
                        {/* Icon */}
                        <div className="flex justify-center">
                            <div className="w-24 h-24 rounded-full bg-gradient-to-br from-red-400 to-red-600 shadow-lg flex items-center justify-center">
                                <svg className="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                        </div>

                        {/* Error Code */}
                        <h1 className="text-7xl font-bold text-teal-700">403</h1>

                        {/* Message */}
                        <div>
                            <h2 className="text-2xl font-semibold text-teal-800 mb-2">Access Denied</h2>
                            <p className="text-teal-600/70">
                                Sorry, you don't have permission to access this page. Please contact your administrator if you believe this is an error.
                            </p>
                        </div>

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-teal-500 to-teal-600 rounded-xl hover:opacity-90 transition-opacity text-white font-medium shadow-lg"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Go to Dashboard
                            </Link>
                            <button
                                onClick={() => window.history.back()}
                                className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white border-2 border-teal-300 rounded-xl hover:bg-teal-50 transition-colors text-teal-700 font-medium"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Go Back
                            </button>
                        </div>

                        {/* Help Link */}
                        <div className="pt-4 border-t border-teal-200/50">
                            <Link
                                href="/support/tickets/create"
                                className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                            >
                                Need help? Contact Support →
                            </Link>
                        </div>
                    </div>
                </div>

                <style>{`
                    @keyframes gradient {
                        0%, 100% { background-position: 0% 50%; }
                        50% { background-position: 100% 50%; }
                    }
                    @keyframes gradient-reverse {
                        0%, 100% { background-position: 100% 50%; }
                        50% { background-position: 0% 50%; }
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
