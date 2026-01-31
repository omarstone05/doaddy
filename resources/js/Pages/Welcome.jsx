import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Receipt, FileText, Calculator, Clock, CheckCircle2, Zap, Users, TrendingUp, Shield, Play, Star } from 'lucide-react';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Addy - Don't fear it. Addy it." />

            <div className="min-h-screen bg-[#FAFBFC] font-sans text-gray-900 selection:bg-[#7DDBA3] selection:text-gray-900 overflow-x-hidden">
                {/* Navbar */}
                <nav className="fixed top-0 left-0 right-0 z-50 px-6 py-4 bg-white/70 backdrop-blur-xl border-b border-gray-100/50">
                    <div className="max-w-7xl mx-auto flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <img src="/assets/logos/size.png" alt="Addy Logo" className="h-9 w-auto object-contain" />
                        </div>

                        <div className="hidden md:flex items-center gap-8">
                            <a href="#features" className="text-gray-600 hover:text-gray-900 font-medium transition-colors text-sm">Features</a>
                            <a href="#pricing" className="text-gray-600 hover:text-gray-900 font-medium transition-colors text-sm">Pricing</a>
                            <a href="#about" className="text-gray-600 hover:text-gray-900 font-medium transition-colors text-sm">About</a>
                        </div>

                        <div className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="px-5 py-2.5 rounded-full bg-gray-900 text-white font-semibold text-sm hover:bg-gray-800 transition-all duration-300"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="hidden md:block px-5 py-2.5 rounded-full text-gray-600 font-medium text-sm hover:text-gray-900 transition-colors"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="px-5 py-2.5 rounded-full bg-gray-900 text-white font-semibold text-sm hover:bg-gray-800 transition-all duration-300"
                                    >
                                        Get Started
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section */}
                <main className="relative z-10 pt-24">
                    <section className="pt-16 pb-24 px-6 relative overflow-hidden">
                        {/* Gradient orbs */}
                        <div className="absolute top-20 left-1/4 w-[500px] h-[500px] bg-gradient-to-r from-[#7DDBA3]/20 to-emerald-200/20 rounded-full blur-[100px] -translate-x-1/2"></div>
                        <div className="absolute top-40 right-1/4 w-[400px] h-[400px] bg-gradient-to-r from-blue-200/20 to-purple-200/20 rounded-full blur-[100px] translate-x-1/2"></div>

                        <div className="max-w-5xl mx-auto text-center relative">
                            {/* Badge */}
                            <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 shadow-sm mb-8">
                                <span className="flex h-2 w-2 rounded-full bg-[#7DDBA3]"></span>
                                <span className="text-sm font-medium text-gray-600">Business accounting made simple</span>
                            </div>

                            {/* Headline */}
                            <h1 className="text-5xl md:text-6xl lg:text-7xl font-bold tracking-tight mb-6 leading-[1.1]">
                                You're not an <span className="text-[#7DDBA3]">accountant.</span><br />
                                You're a business <span className="text-[#7DDBA3]">owner.</span>
                            </h1>

                            {/* Subheadline */}
                            <p className="text-xl md:text-2xl text-gray-500 max-w-2xl mx-auto mb-10 leading-relaxed">
                                Act like it. <span className="text-[#7DDBA3] font-semibold">Addy it.</span>
                            </p>

                            {/* CTA Buttons */}
                            {!auth.user && (
                                <div className="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                                    <Link
                                        href={route('register')}
                                        className="w-full sm:w-auto px-8 py-4 rounded-full bg-gray-900 text-white font-semibold text-lg hover:bg-gray-800 hover:scale-[1.02] transition-all duration-300 flex items-center justify-center gap-2"
                                    >
                                        Start Free Trial <ArrowRight className="w-5 h-5" />
                                    </Link>
                                    <a
                                        href="#demo"
                                        className="w-full sm:w-auto px-8 py-4 rounded-full bg-white border border-gray-200 text-gray-700 font-semibold text-lg hover:border-gray-300 hover:bg-gray-50 transition-all duration-300 flex items-center justify-center gap-2"
                                    >
                                        <Play className="w-5 h-5" /> Watch Demo
                                    </a>
                                </div>
                            )}

                            {/* Social Proof */}
                            <div className="flex flex-col sm:flex-row items-center justify-center gap-6 text-sm text-gray-500">
                                <div className="flex items-center gap-1">
                                    {[...Array(5)].map((_, i) => (
                                        <Star key={i} className="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                    ))}
                                    <span className="ml-2 font-medium">4.9/5</span>
                                </div>
                                <div className="hidden sm:block w-px h-4 bg-gray-300"></div>
                                <span>Trusted by <strong className="text-gray-700">500+</strong> businesses</span>
                                <div className="hidden sm:block w-px h-4 bg-gray-300"></div>
                                <span><strong className="text-gray-700">K2M+</strong> invoices sent</span>
                            </div>
                        </div>

                        {/* Dashboard Preview */}
                        <div className="max-w-6xl mx-auto mt-20 relative">
                            <div className="absolute inset-0 bg-gradient-to-t from-[#FAFBFC] via-transparent to-transparent z-10 pointer-events-none h-full"></div>
                            <div className="bg-white rounded-2xl shadow-2xl shadow-gray-200/50 border border-gray-200/50 overflow-hidden">
                                <div className="h-12 bg-gray-50 border-b border-gray-100 flex items-center px-4 gap-2">
                                    <div className="w-3 h-3 rounded-full bg-red-400"></div>
                                    <div className="w-3 h-3 rounded-full bg-yellow-400"></div>
                                    <div className="w-3 h-3 rounded-full bg-green-400"></div>
                                    <div className="flex-1 flex justify-center">
                                        <div className="h-6 w-64 bg-gray-100 rounded-md"></div>
                                    </div>
                                </div>
                                <div className="p-6 bg-gradient-to-br from-gray-50 to-white min-h-[400px]">
                                    <div className="grid grid-cols-4 gap-4 mb-6">
                                        {[
                                            { label: 'Revenue', value: 'K847,291', change: '+12.5%', color: 'emerald' },
                                            { label: 'Invoices', value: '1,284', change: '+8.2%', color: 'blue' },
                                            { label: 'Customers', value: '492', change: '+23.1%', color: 'purple' },
                                            { label: 'Expenses', value: 'K124,891', change: '-4.3%', color: 'orange' },
                                        ].map((stat, i) => (
                                            <div key={i} className="bg-white rounded-xl p-4 border border-gray-100">
                                                <div className="text-xs text-gray-500 mb-1">{stat.label}</div>
                                                <div className="text-xl font-bold text-gray-900">{stat.value}</div>
                                                <div className={`text-xs font-medium ${stat.change.startsWith('+') ? 'text-emerald-600' : 'text-red-500'}`}>{stat.change}</div>
                                            </div>
                                        ))}
                                    </div>
                                    <div className="grid grid-cols-3 gap-4">
                                        <div className="col-span-2 bg-white rounded-xl p-4 border border-gray-100 h-48"></div>
                                        <div className="bg-white rounded-xl p-4 border border-gray-100 h-48"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Pain Points Section */}
                    <section className="py-24 px-6 bg-white">
                        <div className="max-w-7xl mx-auto">
                            <div className="text-center mb-16">
                                <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                                    Sound familiar?
                                </h2>
                                <p className="text-lg text-gray-500 max-w-2xl mx-auto">
                                    Running a business is hard enough. Don't let the admin work slow you down.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                {[
                                    {
                                        title: "Covered in receipts?",
                                        subtitle: "Buy, Snap and Save.",
                                        icon: <Receipt className="w-6 h-6" />,
                                        description: "Stop drowning in paper. Snap photos of receipts and let Addy organize them automatically."
                                    },
                                    {
                                        title: "Late invoices?",
                                        subtitle: "Autosend Invoices.",
                                        icon: <FileText className="w-6 h-6" />,
                                        description: "Late invoice means late payment. Set up automatic invoice sending and get paid on time."
                                    },
                                    {
                                        title: "Tax season stress?",
                                        subtitle: "Don't fear it.",
                                        icon: <Calculator className="w-6 h-6" />,
                                        description: "Keep your books organized year-round. Tax time becomes a breeze, not a nightmare."
                                    },
                                    {
                                        title: "Can't do it all?",
                                        subtitle: "You don't have to.",
                                        icon: <Clock className="w-6 h-6" />,
                                        description: "Stop trying to be everything. Focus on your business while Addy handles the admin."
                                    }
                                ].map((item, idx) => (
                                    <div key={idx} className="group p-6 rounded-2xl bg-gray-50 hover:bg-white hover:shadow-xl hover:shadow-gray-100 border border-transparent hover:border-gray-100 transition-all duration-300">
                                        <div className="w-12 h-12 rounded-xl bg-[#7DDBA3]/10 text-[#7DDBA3] flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                                            {item.icon}
                                        </div>
                                        <h3 className="text-lg font-bold text-gray-900 mb-1">{item.title}</h3>
                                        <p className="text-[#7DDBA3] font-semibold text-sm mb-3">{item.subtitle} <span className="text-gray-900">Addy it.</span></p>
                                        <p className="text-gray-500 text-sm leading-relaxed">{item.description}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Features Section */}
                    <section id="features" className="py-24 px-6 bg-[#FAFBFC] scroll-mt-24">
                        <div className="max-w-7xl mx-auto">
                            <div className="text-center mb-16">
                                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#7DDBA3]/10 text-[#7DDBA3] text-sm font-medium mb-4">
                                    Features
                                </div>
                                <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                                    Everything you need to run your business
                                </h2>
                                <p className="text-lg text-gray-500 max-w-2xl mx-auto">
                                    Powerful tools that work together seamlessly. No accounting degree required.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {[
                                    {
                                        title: "Invoicing & Quotes",
                                        desc: "Create professional invoices and quotes in seconds. Automatic reminders ensure you get paid on time.",
                                        icon: <FileText className="w-5 h-5" />
                                    },
                                    {
                                        title: "Receipt Management",
                                        desc: "Snap photos of receipts. Addy extracts the data automatically and organizes it for tax time.",
                                        icon: <Receipt className="w-5 h-5" />
                                    },
                                    {
                                        title: "Financial Reports",
                                        desc: "Real-time dashboards show you exactly where your money is going. Make smarter decisions.",
                                        icon: <TrendingUp className="w-5 h-5" />
                                    },
                                    {
                                        title: "Tax Preparation",
                                        desc: "Automatic categorization and reports make tax filing simple. Keep more of what you earn.",
                                        icon: <Calculator className="w-5 h-5" />
                                    },
                                    {
                                        title: "Customer Management",
                                        desc: "Track customer relationships, payment history, and send bulk communications with ease.",
                                        icon: <Users className="w-5 h-5" />
                                    },
                                    {
                                        title: "Smart Automation",
                                        desc: "Set it and forget it. Recurring invoices, automatic reminders, and intelligent categorization.",
                                        icon: <Zap className="w-5 h-5" />
                                    }
                                ].map((feature, idx) => (
                                    <div key={idx} className="group p-6 rounded-2xl bg-white border border-gray-100 hover:border-[#7DDBA3]/30 hover:shadow-lg transition-all duration-300">
                                        <div className="w-10 h-10 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center mb-4 group-hover:bg-[#7DDBA3]/10 group-hover:text-[#7DDBA3] transition-colors duration-300">
                                            {feature.icon}
                                        </div>
                                        <h3 className="text-lg font-semibold text-gray-900 mb-2">{feature.title}</h3>
                                        <p className="text-gray-500 text-sm leading-relaxed">{feature.desc}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Pricing Section */}
                    <section id="pricing" className="py-24 px-6 bg-white scroll-mt-24">
                        <div className="max-w-6xl mx-auto">
                            <div className="text-center mb-16">
                                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#7DDBA3]/10 text-[#7DDBA3] text-sm font-medium mb-4">
                                    Pricing
                                </div>
                                <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                                    Simple, transparent pricing
                                </h2>
                                <p className="text-lg text-gray-500 max-w-2xl mx-auto">
                                    Choose the plan that fits your business. Upgrade or downgrade anytime.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* Starter Plan */}
                                <div className="p-8 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">Starter</h3>
                                    <div className="text-4xl font-bold text-gray-900 mb-1">K500<span className="text-base text-gray-400 font-normal">/mo</span></div>
                                    <p className="text-gray-500 text-sm mb-8">Essential tools for small businesses.</p>

                                    <ul className="space-y-3 mb-8 flex-1">
                                        {[
                                            "Basic Accounting",
                                            "Invoicing & Quotes",
                                            "Up to 3 Users",
                                            "Email Support"
                                        ].map((feature, i) => (
                                            <li key={i} className="flex items-start gap-3 text-sm text-gray-600">
                                                <CheckCircle2 className="w-4 h-4 text-[#7DDBA3] mt-0.5 shrink-0" />
                                                {feature}
                                            </li>
                                        ))}
                                    </ul>

                                    <Link href={route('register')} className="block w-full py-3 px-6 rounded-full border border-gray-200 text-gray-700 font-semibold text-center hover:bg-gray-100 transition-colors text-sm">
                                        Get Started
                                    </Link>
                                </div>

                                {/* Growth Plan */}
                                <div className="p-8 rounded-2xl bg-gray-900 text-white relative flex flex-col">
                                    <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[#7DDBA3] text-gray-900 px-3 py-1 rounded-full text-xs font-bold">
                                        Most Popular
                                    </div>
                                    <h3 className="text-xl font-bold mb-2">Growth</h3>
                                    <div className="text-4xl font-bold mb-1">K2,000<span className="text-base text-gray-400 font-normal">/mo</span></div>
                                    <p className="text-gray-400 text-sm mb-8">For growing teams needing more power.</p>

                                    <ul className="space-y-3 mb-8 flex-1">
                                        {[
                                            "Advanced Reporting",
                                            "Inventory Management",
                                            "Up to 10 Users",
                                            "Payroll (Basic)",
                                            "Priority Support"
                                        ].map((feature, i) => (
                                            <li key={i} className="flex items-start gap-3 text-sm text-gray-300">
                                                <CheckCircle2 className="w-4 h-4 text-[#7DDBA3] mt-0.5 shrink-0" />
                                                {feature}
                                            </li>
                                        ))}
                                    </ul>

                                    <Link href={route('register')} className="block w-full py-3 px-6 rounded-full bg-[#7DDBA3] text-gray-900 font-semibold text-center hover:bg-[#6BCF91] transition-colors text-sm">
                                        Start Free Trial
                                    </Link>
                                </div>

                                {/* Full Suite Plan */}
                                <div className="p-8 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">Full Suite</h3>
                                    <div className="text-4xl font-bold text-gray-900 mb-1">K5,000<span className="text-base text-gray-400 font-normal">/mo</span></div>
                                    <p className="text-gray-500 text-sm mb-8">Complete business management solution.</p>

                                    <ul className="space-y-3 mb-8 flex-1">
                                        {[
                                            "All Features Included",
                                            "Unlimited Users",
                                            "Advanced HR & Payroll",
                                            "CRM & Sales Automation",
                                            "Multi-Currency",
                                            "Dedicated Account Manager"
                                        ].map((feature, i) => (
                                            <li key={i} className="flex items-start gap-3 text-sm text-gray-600">
                                                <CheckCircle2 className="w-4 h-4 text-[#7DDBA3] mt-0.5 shrink-0" />
                                                {feature}
                                            </li>
                                        ))}
                                    </ul>

                                    <Link href={route('register')} className="block w-full py-3 px-6 rounded-full border border-gray-200 text-gray-700 font-semibold text-center hover:bg-gray-100 transition-colors text-sm">
                                        Get Started
                                    </Link>
                                </div>
                            </div>

                            {/* Enterprise */}
                            <div className="text-center mt-12">
                                <Link
                                    href="/enterprise"
                                    className="inline-flex items-center justify-center gap-2 text-gray-500 hover:text-gray-900 font-medium text-sm transition-colors group"
                                >
                                    <Shield className="w-4 h-4" />
                                    Looking for Enterprise?
                                    <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </Link>
                            </div>
                        </div>
                    </section>

                    {/* CTA Section */}
                    <section className="py-24 px-6 bg-[#FAFBFC]">
                        <div className="max-w-4xl mx-auto">
                            <div className="bg-gray-900 rounded-3xl p-12 md:p-16 text-center relative overflow-hidden">
                                {/* Decorative gradient */}
                                <div className="absolute top-0 right-0 w-96 h-96 bg-[#7DDBA3]/20 rounded-full blur-[100px] translate-x-1/2 -translate-y-1/2"></div>

                                <div className="relative">
                                    <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
                                        Ready to take control?
                                    </h2>
                                    <p className="text-lg text-gray-400 mb-8 max-w-xl mx-auto">
                                        Join hundreds of businesses who've stopped stressing about the books and started focusing on what matters.
                                    </p>
                                    {!auth.user && (
                                        <Link
                                            href={route('register')}
                                            className="inline-flex items-center gap-2 px-8 py-4 rounded-full bg-[#7DDBA3] text-gray-900 font-semibold text-lg hover:bg-[#6BCF91] transition-all duration-300"
                                        >
                                            Start Your Free Trial <ArrowRight className="w-5 h-5" />
                                        </Link>
                                    )}
                                    <p className="mt-6 text-[#7DDBA3] font-semibold">
                                        Don't fear it. Addy it.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                {/* Footer */}
                <footer className="py-12 px-6 bg-white border-t border-gray-100">
                    <div className="max-w-7xl mx-auto">
                        <div className="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div className="flex items-center gap-3">
                                <img src="/assets/logos/size.png" alt="Addy Logo" className="h-8 w-auto object-contain" />
                            </div>
                            <p className="text-gray-400 text-sm">&copy; {new Date().getFullYear()} Addy Business. All rights reserved.</p>
                            <div className="flex items-center gap-6">
                                <a href="/privacy" className="text-gray-400 hover:text-gray-600 text-sm transition-colors">Privacy</a>
                                <a href="/terms" className="text-gray-400 hover:text-gray-600 text-sm transition-colors">Terms</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
