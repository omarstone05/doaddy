import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Check, Shield, Zap, Users, ArrowRight, ExternalLink } from 'lucide-react';

export default function Register() {
    const [isRedirecting, setIsRedirecting] = useState(false);
    
    // Build the Penda Cloud registration URL with return_to parameter
    const pendaCloudUrl = 'https://penda.cloud';
    const returnUrl = encodeURIComponent(window.location.origin + '/auth/penda');
    const registerUrl = `${pendaCloudUrl}/register?return_to=${returnUrl}&app=addy`;

    const handleRegister = () => {
        setIsRedirecting(true);
        // Small delay to show the loading state
        setTimeout(() => {
            window.location.href = registerUrl;
        }, 800);
    };

    const benefits = [
        {
            icon: Shield,
            title: 'Secure Single Sign-On',
            description: 'One account for all Penda apps with enterprise-grade security'
        },
        {
            icon: Zap,
            title: 'Instant Access',
            description: 'Get started immediately after registration - no additional setup'
        },
        {
            icon: Users,
            title: 'Team Management',
            description: 'Easily invite team members and manage permissions across apps'
        }
    ];

    const steps = [
        { number: 1, text: 'Create your Penda Cloud account' },
        { number: 2, text: 'Set up your organization' },
        { number: 3, text: 'Access Addy instantly' }
    ];

    return (
        <>
            <Head title="Get Started - Addy" />
            <div className="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                {/* Animated Gradient Background */}
                <div className="fixed inset-0 bg-gradient-to-br from-teal-400 via-mint-300 to-teal-500 animate-gradient">
                    <div className="absolute inset-0 bg-gradient-to-tr from-teal-500/20 via-transparent to-mint-400/20 animate-gradient-reverse"></div>
                </div>

                {/* Glass Container */}
                <div className="relative z-10 max-w-lg w-full">
                    <div className="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 p-8 space-y-6">
                        {/* Header */}
                        <div className="text-center">
                            <div className="flex justify-center mb-4">
                                <div className="w-16 h-16 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 shadow-lg flex items-center justify-center border-4 border-white/50">
                                    <img 
                                        src="/assets/logos/icon.webp" 
                                        alt="Addy" 
                                        className="w-10 h-10 object-contain"
                                        onError={(e) => {
                                            e.target.style.display = 'none';
                                        }}
                                    />
                                </div>
                            </div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                Get Started with Addy
                            </h1>
                            <p className="mt-2 text-gray-600">
                                Your AI-powered business assistant
                            </p>
                        </div>

                        {/* How it works */}
                        <div className="bg-teal-50/50 rounded-xl p-4 border border-teal-100">
                            <p className="text-sm font-medium text-teal-800 mb-3 flex items-center gap-2">
                                <span className="text-lg">✨</span>
                                Quick 3-step setup
                            </p>
                            <div className="flex items-center justify-between">
                                {steps.map((step, index) => (
                                    <div key={step.number} className="flex items-center">
                                        <div className="flex flex-col items-center">
                                            <div className="w-8 h-8 rounded-full bg-teal-500 text-white flex items-center justify-center text-sm font-bold">
                                                {step.number}
                                            </div>
                                            <p className="text-xs text-teal-700 mt-1 text-center max-w-[80px]">
                                                {step.text}
                                            </p>
                                        </div>
                                        {index < steps.length - 1 && (
                                            <ArrowRight className="w-4 h-4 text-teal-400 mx-2 mt-[-20px]" />
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Benefits */}
                        <div className="space-y-3">
                            {benefits.map((benefit) => (
                                <div key={benefit.title} className="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div className="w-10 h-10 rounded-lg bg-teal-100 flex items-center justify-center flex-shrink-0">
                                        <benefit.icon className="w-5 h-5 text-teal-600" />
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-gray-900 text-sm">{benefit.title}</h3>
                                        <p className="text-gray-500 text-xs">{benefit.description}</p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* CTA Button */}
                        <div className="space-y-4 pt-2">
                            <button
                                onClick={handleRegister}
                                disabled={isRedirecting}
                                className="w-full flex items-center justify-center gap-3 px-6 py-4 bg-gradient-to-r from-teal-500 to-teal-600 rounded-xl hover:from-teal-600 hover:to-teal-700 transition-all text-white font-semibold shadow-lg disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                {isRedirecting ? (
                                    <>
                                        <div className="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
                                        <span>Redirecting to Penda Cloud...</span>
                                    </>
                                ) : (
                                    <>
                                        <span>Create Free Account</span>
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
                        </div>

                        {/* Divider */}
                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-gray-200"></div>
                            </div>
                            <div className="relative flex justify-center text-sm">
                                <span className="px-3 bg-white text-gray-500">Already have an account?</span>
                            </div>
                        </div>

                        {/* Login link */}
                        <div className="text-center">
                            <Link
                                href="/login"
                                className="inline-flex items-center gap-2 text-teal-600 hover:text-teal-700 font-medium transition-colors"
                            >
                                Sign in to Addy
                                <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>

                        {/* Trust badges */}
                        <div className="flex items-center justify-center gap-4 pt-2 text-xs text-gray-400">
                            <div className="flex items-center gap-1">
                                <Check className="w-3 h-3" />
                                <span>Free to start</span>
                            </div>
                            <div className="flex items-center gap-1">
                                <Check className="w-3 h-3" />
                                <span>No credit card</span>
                            </div>
                            <div className="flex items-center gap-1">
                                <Check className="w-3 h-3" />
                                <span>Cancel anytime</span>
                            </div>
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
