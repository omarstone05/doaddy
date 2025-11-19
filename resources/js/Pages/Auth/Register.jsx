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
                            <div>
                                <Button type="submit" className="w-full" disabled={processing || (data.password && !isPasswordValid)}>
                                    Create Account
                                </Button>
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

