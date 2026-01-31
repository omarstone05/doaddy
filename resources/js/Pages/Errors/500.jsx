import { Head, Link } from '@inertiajs/react';

export default function ServerError() {
    return (
        <>
            <Head title="Server Error - Addy" />
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
                            <div className="w-24 h-24 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 shadow-lg flex items-center justify-center">
                                <svg className="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>

                        {/* Error Code */}
                        <h1 className="text-7xl font-bold text-teal-700">500</h1>

                        {/* Message */}
                        <div>
                            <h2 className="text-2xl font-semibold text-teal-800 mb-2">Server Error</h2>
                            <p className="text-teal-600/70">
                                Oops! Something went wrong on our end. Our team has been notified and we're working to fix it.
                            </p>
                        </div>

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                            <button
                                onClick={() => window.location.reload()}
                                className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-teal-500 to-teal-600 rounded-xl hover:opacity-90 transition-opacity text-white font-medium shadow-lg"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Try Again
                            </button>
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white border-2 border-teal-300 rounded-xl hover:bg-teal-50 transition-colors text-teal-700 font-medium"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Go to Dashboard
                            </Link>
                        </div>

                        {/* Help Link */}
                        <div className="pt-4 border-t border-teal-200/50">
                            <Link
                                href="/support/tickets/create"
                                className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                            >
                                Report this issue →
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
