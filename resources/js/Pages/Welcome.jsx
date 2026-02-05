import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { ArrowRight, Receipt, FileText, Calculator, Clock, CheckCircle2, Zap, Users, TrendingUp, Shield, Star } from 'lucide-react';

export default function Welcome({ auth }) {
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 400);
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const navLight = !scrolled;

    return (
        <>
            <Head title="Addy - Don't fear it. Addy it." />

            <div className="min-h-screen bg-[#FAFBFC] font-sans text-gray-900 selection:bg-[#7DDBA3] selection:text-gray-900 overflow-x-hidden">
                {/* Navbar - no background at top, clear glass only on scroll */}
                <nav className={`fixed top-0 left-0 right-0 z-50 px-6 py-4 transition-all duration-300 ${navLight ? 'bg-transparent' : 'bg-white/20 backdrop-blur-md border-b border-gray-200/50'}`}>
                    <div className="max-w-7xl mx-auto flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <img src="/assets/logos/size.webp" alt="Addy Logo" className="h-14 w-auto object-contain brightness-0 invert" />
                        </div>

                        <div className="hidden md:flex items-center gap-8">
                            <a href="#features" className={`font-medium transition-colors text-sm ${navLight ? 'text-white/90 hover:text-white' : 'text-gray-600 hover:text-gray-900'}`}>Features</a>
                            <a href="#pricing" className={`font-medium transition-colors text-sm ${navLight ? 'text-white/90 hover:text-white' : 'text-gray-600 hover:text-gray-900'}`}>Pricing</a>
                            <a href="#about" className={`font-medium transition-colors text-sm ${navLight ? 'text-white/90 hover:text-white' : 'text-gray-600 hover:text-gray-900'}`}>About</a>
                        </div>

                        <div className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className={`px-5 py-2.5 rounded-full font-semibold text-sm backdrop-blur-sm transition-all duration-300 ${navLight ? 'bg-white/20 text-white hover:bg-white/30 border border-white/30' : 'bg-gray-900 text-white hover:bg-gray-800 border border-transparent'}`}
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className={`hidden md:block px-5 py-2.5 rounded-full font-medium text-sm transition-colors ${navLight ? 'text-white/90 hover:text-white' : 'text-gray-600 hover:text-gray-900'}`}
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className={`px-5 py-2.5 rounded-full font-semibold text-sm backdrop-blur-sm transition-all duration-300 ${navLight ? 'bg-white/20 text-white hover:bg-white/30 border border-white/30' : 'bg-gray-900 text-white hover:bg-gray-800 border border-transparent'}`}
                                    >
                                        Get Started
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section - Parallax image with text overlay, extends behind header */}
                <main className="relative z-10">
                    <section className="relative overflow-hidden -mt-16 pt-16">
                        {/* Parallax Hero Image - extends to top so hero shows through transparent header */}
                        <div className="relative w-full min-h-[85vh] pt-5 pb-[70px]">
                            <div
                                className="absolute inset-0 bg-cover bg-center bg-fixed"
                                style={{ backgroundImage: "url('/images/hero.webp')" }}
                            />
                            <div className="absolute inset-0 bg-black/20" />

                            {/* Text overlay - aligned right, larger text, pt for nav clearance */}
                            <div
                                className="absolute inset-0 flex flex-col items-end justify-center pt-24 px-8 md:px-16 lg:px-24"
                                style={{ fontFamily: "'Gochi Hand', cursive" }}
                            >
                                <div className="text-right space-y-0.5 md:space-y-1 leading-tight">
                                    <p className="text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-white line-through drop-shadow-lg">
                                        Accountant
                                    </p>
                                    <p className="text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-white line-through drop-shadow-lg">
                                        Analyst
                                    </p>
                                    <p className="text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-[#7DDBA3] font-semibold drop-shadow-lg">
                                        Business Owner
                                    </p>
                                </div>

                                {/* Hero body text - Noir Pro Regular (DM Sans fallback) */}
                                <p
                                    className="text-base md:text-lg text-white/95 max-w-md text-right mt-10 drop-shadow-md"
                                    style={{ fontFamily: "'Noir Pro', 'DM Sans', sans-serif" }}
                                >
                                    [Your hero body text goes here – placeholder for now]
                                </p>

                                {/* CTA Button - Noir Pro Regular */}
                                {!auth.user && (
                                    <Link
                                        href={route('register')}
                                        className="mt-8 px-8 py-4 rounded-full bg-white/20 text-white font-normal text-lg hover:bg-white/30 backdrop-blur-sm transition-all duration-300 flex items-center justify-center gap-2 border border-white/40"
                                        style={{ fontFamily: "'Noir Pro', 'DM Sans', sans-serif" }}
                                    >
                                        Manage your business better <ArrowRight className="w-5 h-5" />
                                    </Link>
                                )}
                            </div>
                        </div>

                        {/* Social Proof - below image */}
                        <div className="flex flex-col sm:flex-row items-center justify-center gap-6 text-sm text-gray-500 py-12 px-6">
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
                    </section>

                    {/* Pain Points Section */}
                    <section className="py-24 px-6 bg-white">
                        <div className="max-w-7xl mx-auto">
                            <div className="text-center mb-16">
                                <h2 className="text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold text-gray-900 mb-4" style={{ fontFamily: "'Gochi Hand', cursive" }}>
                                    Business should <span className="text-[#7DDBA3]">NOT</span> be Difficult.
                                </h2>
                                <p className="text-lg text-gray-500 max-w-2xl mx-auto">
                                    You got in business to do great things. Well, do them. Let us focus on the admin, you focus on the craft.
                                </p>
                            </div>

                            {/* Dashboard with placeholder squares (to be replaced by floating feature cards) */}
                            <div className="max-w-5xl mx-auto mb-20">
                                <div className="relative rounded-2xl overflow-hidden shadow-2xl shadow-gray-200/80 border border-gray-100 ring-1 ring-black/5">
                                    <img
                                        src="/images/dashboard-screenshot.png"
                                        alt="Addy Dashboard - Revenue, expenses, cash flow, and business progress at a glance"
                                        className="w-full h-auto object-contain block"
                                    />
                                    {/* Placeholder squares on dashboard - will be replaced by floating feature cards */}
                                    <div className="absolute inset-0 pointer-events-none" aria-hidden>
                                        {[
                                            { top: '12%', left: '8%', size: 'w-16 h-16 md:w-20 md:h-20', delay: 0 },
                                            { top: '12%', left: '32%', size: 'w-14 h-14 md:w-16 md:h-16', delay: 0.1 },
                                            { top: '12%', left: '56%', size: 'w-16 h-16 md:w-20 md:h-20', delay: 0.2 },
                                            { top: '12%', right: '8%', left: 'auto', size: 'w-14 h-14 md:w-16 md:h-16', delay: 0.3 },
                                            { top: '38%', left: '12%', size: 'w-20 h-20 md:w-24 md:h-24', delay: 0.25 },
                                            { top: '35%', right: '12%', left: 'auto', size: 'w-16 h-16 md:w-20 md:h-20', delay: 0.35 },
                                            { bottom: '28%', left: '15%', size: 'w-16 h-16 md:w-20 md:h-20', delay: 0.4 },
                                            { bottom: '28%', right: '15%', left: 'auto', size: 'w-14 h-14 md:w-16 md:h-16', delay: 0.5 },
                                        ].map((sq, i) => (
                                            <div
                                                key={i}
                                                className={`absolute rounded-lg bg-[#7DDBA3]/25 border border-[#7DDBA3]/40 ${sq.size}`}
                                                style={{
                                                    top: sq.top,
                                                    bottom: sq.bottom,
                                                    left: sq.left,
                                                    right: sq.right,
                                                    animation: `fadeInSquare 0.8s ease-out ${sq.delay}s forwards`,
                                                    opacity: 0,
                                                }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <style>{`
                                @keyframes fadeInSquare {
                                    from { opacity: 0; transform: scale(0.8); }
                                    to { opacity: 1; transform: scale(1); }
                                }
                            `}</style>

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
                                        <h3 className="text-lg font-bold text-gray-900 mb-1" style={{ fontFamily: "'Gochi Hand', cursive" }}>{item.title}</h3>
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
                                <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4" style={{ fontFamily: "'Gochi Hand', cursive" }}>
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
                                        <h3 className="text-lg font-semibold text-gray-900 mb-2" style={{ fontFamily: "'Gochi Hand', cursive" }}>{feature.title}</h3>
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
                                <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4" style={{ fontFamily: "'Gochi Hand', cursive" }}>
                                    Simple, transparent pricing
                                </h2>
                                <p className="text-lg text-gray-500 max-w-2xl mx-auto">
                                    Choose the plan that fits your business. Upgrade or downgrade anytime.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* Starter Plan */}
                                <div className="p-8 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2" style={{ fontFamily: "'Gochi Hand', cursive" }}>Starter</h3>
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
                                    <h3 className="text-xl font-bold mb-2" style={{ fontFamily: "'Gochi Hand', cursive" }}>Growth</h3>
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
                                    <h3 className="text-xl font-bold text-gray-900 mb-2" style={{ fontFamily: "'Gochi Hand', cursive" }}>Full Suite</h3>
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
                                    <h2 className="text-3xl md:text-4xl font-bold text-white mb-4" style={{ fontFamily: "'Gochi Hand', cursive" }}>
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
                                <img src="/assets/logos/size.webp" alt="Addy Logo" className="h-8 w-auto object-contain" />
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
