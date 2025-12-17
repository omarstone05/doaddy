import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, LayoutDashboard, ShieldCheck, Zap, Check } from 'lucide-react';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Welcome to Addy" />

            <div className="min-h-screen relative overflow-hidden font-sans text-gray-900 selection:bg-teal-500 selection:text-white">
                {/* Background Gradients */}
                <div className="absolute inset-0 z-0">
                    <div className="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-teal-200/30 blur-[120px] animate-move-circle-slow"></div>
                    <div className="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-mint-200/30 blur-[120px] animate-move-circle-reverse"></div>
                    <div className="absolute top-[40%] left-[40%] w-[30%] h-[30%] rounded-full bg-blue-100/30 blur-[100px] animate-move-vertical"></div>
                </div>

                {/* Navbar */}
                <nav className="relative z-50 px-6 py-6">
                    <div className="max-w-7xl mx-auto flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <img src="/assets/logos/size.png" alt="Addy Logo" className="h-10 w-auto object-contain" />
                        </div>

                        <div className="hidden md:flex items-center gap-8">
                            <a href="#features" className="text-gray-600 hover:text-teal-700 font-medium transition-colors">Features</a>
                            <a href="#pricing" className="text-gray-600 hover:text-teal-700 font-medium transition-colors">Pricing</a>
                            <a href="#about" className="text-gray-600 hover:text-teal-700 font-medium transition-colors">About</a>
                        </div>

                        <div className="flex items-center gap-4">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="px-6 py-2.5 rounded-xl bg-white/80 backdrop-blur-md border border-white/50 text-teal-800 font-semibold shadow-sm hover:bg-white hover:shadow-md transition-all duration-300"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="hidden md:block px-6 py-2.5 rounded-xl text-gray-600 font-medium hover:text-teal-700 transition-colors"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="px-6 py-2.5 rounded-xl bg-gradient-to-r from-teal-600 to-teal-500 text-white font-semibold shadow-lg shadow-teal-500/30 hover:shadow-teal-500/40 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2"
                                    >
                                        Get Started <ArrowRight className="w-4 h-4" />
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section */}
                <main className="relative z-10 pt-16 pb-24 px-6">
                    <div className="max-w-7xl mx-auto text-center mb-16">
                        <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/60 backdrop-blur-md border border-white/60 shadow-sm mb-8 animate-fade-in-up">
                            <span className="flex h-2 w-2 rounded-full bg-teal-500"></span>
                            <span className="text-sm font-medium text-teal-800">Addy Business 2.0 is here</span>
                        </div>

                        <h1 className="text-5xl md:text-7xl font-bold tracking-tight text-gray-900 mb-6 leading-tight">
                            Manage your business <br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-mint-600">
                                with crystal clarity
                            </span>
                        </h1>

                        <p className="text-xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
                            The all-in-one platform for modern businesses. Handle finances, HR, CRM, and more in a beautifully designed, intuitive interface.
                        </p>

                        {!auth.user && (
                            <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
                                <Link
                                    href={route('register')}
                                    className="w-full sm:w-auto px-8 py-4 rounded-2xl bg-teal-600 text-white font-bold text-lg shadow-xl shadow-teal-500/20 hover:bg-teal-700 hover:shadow-2xl hover:shadow-teal-500/30 hover:-translate-y-1 transition-all duration-300"
                                >
                                    Start Free Trial
                                </Link>
                                <a
                                    href="#demo"
                                    className="w-full sm:w-auto px-8 py-4 rounded-2xl bg-white/50 backdrop-blur-sm border border-white/60 text-gray-700 font-bold text-lg hover:bg-white/80 transition-all duration-300"
                                >
                                    View Demo
                                </a>
                            </div>
                        )}
                    </div>

                    {/* Glass Card Showcase */}
                    <div className="max-w-6xl mx-auto relative mb-32">
                        {/* Decorative elements behind the card */}
                        <div className="absolute -top-12 -left-12 w-24 h-24 bg-yellow-400/20 rounded-full blur-xl animate-pulse"></div>
                        <div className="absolute -bottom-12 -right-12 w-32 h-32 bg-teal-400/20 rounded-full blur-xl animate-pulse delay-700"></div>

                        {/* Main Glass Card */}
                        <div className="glass-strong rounded-3xl p-2 md:p-4 shadow-2xl shadow-teal-900/5 overflow-hidden border border-white/60">
                            <div className="bg-white/50 rounded-2xl overflow-hidden border border-white/40 min-h-[400px] md:min-h-[600px] relative">
                                {/* Mock UI Header */}
                                <div className="h-16 border-b border-gray-100 flex items-center justify-between px-6 bg-white/40 backdrop-blur-sm">
                                    <div className="flex items-center gap-4">
                                        <div className="flex gap-2">
                                            <div className="w-3 h-3 rounded-full bg-red-400/80"></div>
                                            <div className="w-3 h-3 rounded-full bg-yellow-400/80"></div>
                                            <div className="w-3 h-3 rounded-full bg-green-400/80"></div>
                                        </div>
                                        <div className="h-8 w-64 bg-gray-100/50 rounded-lg ml-4"></div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <div className="h-8 w-8 rounded-full bg-gray-200/50"></div>
                                        <div className="h-8 w-24 rounded-lg bg-teal-500/10"></div>
                                    </div>
                                </div>

                                {/* Mock UI Content */}
                                <div className="p-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                                    {/* Sidebar */}
                                    <div className="hidden md:block space-y-4">
                                        {[1, 2, 3, 4, 5].map((i) => (
                                            <div key={i} className="h-10 w-full rounded-xl bg-gray-50/50 animate-pulse" style={{ animationDelay: `${i * 100}ms` }}></div>
                                        ))}
                                    </div>

                                    {/* Main Content */}
                                    <div className="md:col-span-2 space-y-6">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="h-32 rounded-2xl bg-gradient-to-br from-teal-50/80 to-white border border-teal-100 p-4 relative overflow-hidden group hover:shadow-lg transition-all cursor-pointer">
                                                <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                                    <Zap className="w-16 h-16 text-teal-600" />
                                                </div>
                                                <div className="h-8 w-8 rounded-lg bg-teal-100 text-teal-600 flex items-center justify-center mb-4">
                                                    <Zap className="w-5 h-5" />
                                                </div>
                                                <div className="text-2xl font-bold text-gray-800">24%</div>
                                                <div className="text-sm text-gray-500">Growth this month</div>
                                            </div>
                                            <div className="h-32 rounded-2xl bg-gradient-to-br from-mint-50/80 to-white border border-mint-100 p-4 relative overflow-hidden group hover:shadow-lg transition-all cursor-pointer">
                                                <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                                    <ShieldCheck className="w-16 h-16 text-mint-600" />
                                                </div>
                                                <div className="h-8 w-8 rounded-lg bg-mint-100 text-mint-600 flex items-center justify-center mb-4">
                                                    <ShieldCheck className="w-5 h-5" />
                                                </div>
                                                <div className="text-2xl font-bold text-gray-800">100%</div>
                                                <div className="text-sm text-gray-500">Secure & Compliant</div>
                                            </div>
                                        </div>

                                        <div className="h-64 rounded-2xl bg-white/60 border border-gray-100 p-6 relative">
                                            <div className="flex items-center justify-between mb-6">
                                                <div className="h-6 w-32 bg-gray-100 rounded"></div>
                                                <div className="h-8 w-24 bg-teal-50 rounded-lg"></div>
                                            </div>
                                            <div className="space-y-3">
                                                <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                                    <div className="h-full w-3/4 bg-teal-400 rounded-full"></div>
                                                </div>
                                                <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                                    <div className="h-full w-1/2 bg-mint-400 rounded-full"></div>
                                                </div>
                                                <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                                    <div className="h-full w-5/6 bg-blue-400 rounded-full"></div>
                                                </div>
                                            </div>

                                            {/* Floating Elements */}
                                            <div className="absolute bottom-6 right-6 bg-white rounded-xl shadow-lg p-4 border border-gray-100 animate-bounce-slow">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-600">
                                                        <LayoutDashboard className="w-5 h-5" />
                                                    </div>
                                                    <div>
                                                        <div className="text-sm font-bold text-gray-800">New Report</div>
                                                        <div className="text-xs text-gray-500">Just created</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Features Grid */}
                    <div id="features" className="max-w-7xl mx-auto mb-32 scroll-mt-24">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl font-bold text-gray-900 mb-4">Everything you need to run your business</h2>
                            <p className="text-gray-600 max-w-2xl mx-auto">Powerful tools integrated into one seamless platform.</p>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {[
                                {
                                    title: "Financial Clarity",
                                    desc: "Track every penny with our advanced accounting and reporting tools.",
                                    icon: <LayoutDashboard className="w-6 h-6 text-teal-600" />
                                },
                                {
                                    title: "Team Management",
                                    desc: "Streamline HR, payroll, and performance tracking in one place.",
                                    icon: <CheckCircle2 className="w-6 h-6 text-mint-600" />
                                },
                                {
                                    title: "Smart Automation",
                                    desc: "Let AI handle the boring stuff while you focus on growing your business.",
                                    icon: <Zap className="w-6 h-6 text-blue-600" />
                                }
                            ].map((feature, idx) => (
                                <div key={idx} className="glass-card p-8 hover:bg-white/60 transition-colors group">
                                    <div className="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                                        {feature.icon}
                                    </div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-3">{feature.title}</h3>
                                    <p className="text-gray-600 leading-relaxed">
                                        {feature.desc}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Pricing Section */}
                    <div id="pricing" className="max-w-7xl mx-auto mb-24 scroll-mt-24">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl font-bold text-gray-900 mb-4">Simple, transparent pricing</h2>
                            <p className="text-gray-600 max-w-2xl mx-auto">Choose the plan that fits your business needs.</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                            {/* Starter Plan */}
                            <div className="glass-card p-6 relative hover:bg-white/60 transition-colors flex flex-col">
                                <h3 className="text-xl font-bold text-gray-900 mb-2">Starter</h3>
                                <div className="text-3xl font-bold text-gray-900 mb-4">K500<span className="text-sm text-gray-500 font-normal">/mo</span></div>
                                <p className="text-sm text-gray-600 mb-6">Essential tools for small businesses.</p>

                                <ul className="space-y-3 mb-8 flex-1">
                                    {[
                                        "Basic Accounting",
                                        "Invoicing & Quotes",
                                        "Up to 3 Users",
                                        "Email Support"
                                    ].map((feature, i) => (
                                        <li key={i} className="flex items-start gap-2 text-sm text-gray-600">
                                            <Check className="w-4 h-4 text-teal-500 mt-0.5 shrink-0" />
                                            {feature}
                                        </li>
                                    ))}
                                </ul>

                                <Link href={route('register')} className="block w-full py-2.5 px-4 rounded-xl border border-teal-200 text-teal-700 font-semibold text-center hover:bg-teal-50 transition-colors text-sm">
                                    Get Started
                                </Link>
                            </div>

                            {/* Growth Plan */}
                            <div className="glass-strong p-6 relative transform md:-translate-y-4 border-teal-200 shadow-xl shadow-teal-900/5 flex flex-col">
                                <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-teal-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg whitespace-nowrap">
                                    Most Popular
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-2">Growth</h3>
                                <div className="text-3xl font-bold text-gray-900 mb-4">K2,000<span className="text-sm text-gray-500 font-normal">/mo</span></div>
                                <p className="text-sm text-gray-600 mb-6">For growing teams needing more power.</p>

                                <ul className="space-y-3 mb-8 flex-1">
                                    {[
                                        "Advanced Reporting",
                                        "Inventory Management",
                                        "Up to 10 Users",
                                        "Payroll (Basic)",
                                        "Priority Support"
                                    ].map((feature, i) => (
                                        <li key={i} className="flex items-start gap-2 text-sm text-gray-800 font-medium">
                                            <Check className="w-4 h-4 text-teal-600 mt-0.5 shrink-0" />
                                            {feature}
                                        </li>
                                    ))}
                                </ul>

                                <Link href={route('register')} className="block w-full py-2.5 px-4 rounded-xl bg-teal-600 text-white font-bold text-center shadow-lg shadow-teal-500/20 hover:bg-teal-700 hover:shadow-teal-500/30 transition-all text-sm">
                                    Start Free Trial
                                </Link>
                            </div>

                            {/* Full Suite Plan */}
                            <div className="glass-card p-6 relative hover:bg-white/60 transition-colors flex flex-col">
                                <h3 className="text-xl font-bold text-gray-900 mb-2">Full Suite</h3>
                                <div className="text-3xl font-bold text-gray-900 mb-4">K5,000<span className="text-sm text-gray-500 font-normal">/mo</span></div>
                                <p className="text-sm text-gray-600 mb-6">Complete business management solution.</p>

                                <ul className="space-y-3 mb-8 flex-1">
                                    {[
                                        "All Features Included",
                                        "Unlimited Users",
                                        "Advanced HR & Payroll",
                                        "CRM & Sales Automation",
                                        "Multi-Currency",
                                        "Dedicated Account Manager"
                                    ].map((feature, i) => (
                                        <li key={i} className="flex items-start gap-2 text-sm text-gray-600">
                                            <Check className="w-4 h-4 text-teal-500 mt-0.5 shrink-0" />
                                            {feature}
                                        </li>
                                    ))}
                                </ul>

                                <Link href={route('register')} className="block w-full py-2.5 px-4 rounded-xl border border-teal-200 text-teal-700 font-semibold text-center hover:bg-teal-50 transition-colors text-sm">
                                    Get Started
                                </Link>
                            </div>
                        </div>

                        {/* Enterprise Button */}
                        <div className="text-center">
                            <Link
                                href="/enterprise"
                                className="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl bg-white/50 backdrop-blur-sm border border-gray-200 text-gray-700 font-bold text-lg hover:bg-white/80 hover:shadow-lg transition-all duration-300 group"
                            >
                                <ShieldCheck className="w-5 h-5 text-teal-600 group-hover:scale-110 transition-transform" />
                                Looking for a Custom Enterprise Solution?
                                <ArrowRight className="w-5 h-5 text-gray-400 group-hover:text-teal-600 group-hover:translate-x-1 transition-all" />
                            </Link>
                        </div>
                    </div>
                </main>

                {/* Footer */}
                <footer className="relative z-10 py-12 text-center text-gray-500 text-sm border-t border-white/20 bg-white/30 backdrop-blur-md">
                    <div className="max-w-7xl mx-auto px-6">
                        <div className="flex items-center justify-center gap-2 mb-4">
                            <img src="/assets/logos/icon.png" alt="Addy Logo" className="w-6 h-6 opacity-80" />
                            <span className="font-bold text-gray-700">Addy</span>
                        </div>
                        <p>&copy; {new Date().getFullYear()} Addy Business. All rights reserved.</p>
                    </div>
                </footer>
            </div>

            <style>{`
                .animate-bounce-slow {
                    animation: bounce 3s infinite;
                }
                @keyframes bounce {
                    0%, 100% { transform: translateY(-5%); }
                    50% { transform: translateY(5%); }
                }
            `}</style>
        </>
    );
}
