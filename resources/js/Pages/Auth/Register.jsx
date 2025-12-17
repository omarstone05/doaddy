import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/Button';
import { useState } from 'react';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        organization_name: '',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [passwordStrength, setPasswordStrength] = useState({
        length: false,
        hasNumber: false,
        hasSpecial: false,
    });

    const checkPasswordStrength = (password) => {
        setPasswordStrength({
            length: password.length >= 8,
            hasNumber: /\d/.test(password),
            hasSpecial: /[!@#$%^&*(),.?":{}|<>]/.test(password),
        });
    };

    const isPasswordValid = passwordStrength.length && (passwordStrength.hasNumber || passwordStrength.hasSpecial);

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <>
            <Head title="Register" />
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
                                Create your account
                            </h2>
                            <p className="mt-2 text-sm text-teal-600/70">
                                Or{' '}
                                <Link href="/login" className="font-medium text-teal-600 hover:text-teal-700 underline">
                                    sign in to your existing account
                                </Link>
                            </p>
                        </div>

                        <form className="space-y-6" onSubmit={submit}>
                            {/* Error Message - Show at top if any errors */}
                            {errors.error && (
                                <div className="bg-red-50 border-2 border-red-300 text-red-700 px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-2">
                                    <svg className="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                    </svg>
                                    <span>{errors.error}</span>
                                </div>
                            )}
                            
                            <div className="space-y-4">
                                <div>
                                    <label htmlFor="organization_name" className="block text-sm font-medium text-gray-700 mb-1">
                                        Organization Name
                                    </label>
                                    <input
                                        id="organization_name"
                                        type="text"
                                        value={data.organization_name}
                                        onChange={(e) => setData('organization_name', e.target.value)}
                                        className="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-teal-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 text-gray-900 placeholder:text-gray-400"
                                        placeholder="Your business name"
                                        required
                                    />
                                    {errors.organization_name && <p className="mt-1 text-sm text-red-600">{errors.organization_name}</p>}
                                </div>
                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                                        Your Name
                                    </label>
                                    <input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-teal-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 text-gray-900 placeholder:text-gray-400"
                                        placeholder="John Doe"
                                        required
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                                </div>
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
                                        onChange={(e) => {
                                            setData('password', e.target.value);
                                            checkPasswordStrength(e.target.value);
                                        }}
                                        className="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-teal-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 text-gray-900 placeholder:text-gray-400"
                                        placeholder="••••••••"
                                        required
                                    />
                                    {data.password && (
                                        <div className="mt-2 space-y-1">
                                            <p className="text-xs text-gray-600 font-medium">Password requirements:</p>
                                            <div className="space-y-1 text-xs">
                                                <div className={`flex items-center ${passwordStrength.length ? 'text-green-600' : 'text-gray-500'}`}>
                                                    <span className={`mr-2 ${passwordStrength.length ? '✓' : '○'}`}>
                                                        {passwordStrength.length ? '✓' : '○'}
                                                    </span>
                                                    At least 8 characters
                                                </div>
                                                <div className={`flex items-center ${passwordStrength.hasNumber || passwordStrength.hasSpecial ? 'text-green-600' : 'text-gray-500'}`}>
                                                    <span className={`mr-2 ${passwordStrength.hasNumber || passwordStrength.hasSpecial ? '✓' : '○'}`}>
                                                        {passwordStrength.hasNumber || passwordStrength.hasSpecial ? '✓' : '○'}
                                                    </span>
                                                    Contains a number or special character (!@#$%^&*)
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                    {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                                </div>
                                <div>
                                    <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 mb-1">
                                        Confirm Password
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        className="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-teal-200/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300 text-gray-900 placeholder:text-gray-400"
                                        placeholder="••••••••"
                                        required
                                    />
                                </div>
                            </div>
                            <div className="space-y-3">
                                <Button type="submit" className="w-full" disabled={processing || (data.password && !isPasswordValid)}>
                                    Create Account
                                </Button>
                                
                                {/* Divider */}
                                <div className="relative">
                                    <div className="absolute inset-0 flex items-center">
                                        <div className="w-full border-t border-teal-200/50"></div>
                                    </div>
                                    <div className="relative flex justify-center text-sm">
                                        <span className="px-2 bg-white/80 text-gray-500">Or continue with</span>
                                    </div>
                                </div>

                                {/* Google Login Button */}
                                <a
                                    href="/auth/google/login"
                                    className="w-full flex items-center justify-center gap-3 px-4 py-3 bg-white border border-teal-200/50 rounded-xl hover:bg-teal-50/50 transition-colors text-gray-700 font-medium"
                                >
                                    <svg className="w-5 h-5" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    Sign up with Google
                                </a>
                            </div>
                        </form>
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

