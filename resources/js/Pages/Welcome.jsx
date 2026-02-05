import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import {
    ArrowRight,
    Receipt,
    FileText,
    Calculator,
    Clock,
    CheckCircle2,
    Users,
    Star,
    ChevronDown,
    ChevronUp,
    Shield,
} from 'lucide-react';

export default function Welcome({ auth, stats = {} }) {
    const [scrolled, setScrolled] = useState(false);
    const [openFaq, setOpenFaq] = useState(null);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 400);
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const navLight = !scrolled;

    const formatInvoiced = (amount) => {
        if (amount >= 1_000_000) return `K${(amount / 1_000_000).toFixed(1)}M+`;
        if (amount >= 1_000) return `K${(amount / 1_000).toFixed(0)}K+`;
        return `K${Math.round(amount)}+`;
    };

    const statItems = [
        { label: 'Trusted since 2019', value: '2019', icon: Star },
        { label: 'invoiced through Addy', value: formatInvoiced(stats.totalInvoiced ?? 0), icon: FileText },
        { label: 'businesses running on Addy', value: (stats.businessCount ?? 0).toLocaleString(), icon: Users },
    ];

    const painPointCards = [
        {
            title: 'Covered in receipts?',
            subtitle: 'Buy, Snap and Save.',
            icon: Receipt,
            description: 'Stop drowning in paper. Snap photos of receipts and let Addy organize them automatically.',
            image: '/images/pain-receipts.png',
        },
        {
            title: 'Late invoices?',
            subtitle: 'Autosend Invoices.',
            icon: FileText,
            description: 'Late invoice means late payment. Set up automatic invoice sending and get paid on time.',
            image: '/images/pain-invoices.png',
        },
        {
            title: 'Tax season stress?',
            subtitle: "Don't fear it.",
            icon: Calculator,
            description: 'Keep your books organized year-round. Tax time becomes a breeze, not a nightmare.',
            image: '/images/pain-tax.png',
        },
        {
            title: "Can't do it all?",
            subtitle: "You don't have to.",
            icon: Clock,
            description: 'Stop trying to be everything. Focus on your business while Addy handles the admin.',
            image: '/images/pain-cant-do-it-all.png',
        },
    ];

    const faqs = [
        { q: 'What does Addy include?', a: 'Addy includes invoicing, quotes, receipt management, financial reports, tax preparation tools, customer management, and smart automation. Plans scale from solo entrepreneurs to growing teams.' },
        { q: 'Can I try Addy for free?', a: 'Yes. Start with a free trial on any plan. No credit card required. Upgrade or downgrade anytime.' },
        { q: 'Is my data secure?', a: 'Absolutely. We use industry-standard encryption and security practices. Your business data is backed up and protected.' },
        { q: 'Do I need accounting experience?', a: 'No. Addy is built for business owners, not accountants. The interface is intuitive and we provide guidance every step of the way.' },
    ];

    return (
        <>
            <Head title="Addy - Don't fear it. Addy it." />

            <div className="min-h-screen bg-white font-sans text-gray-900 selection:bg-[#7DDBA3] selection:text-gray-900 overflow-x-hidden">
                {/* Navbar */}
                <nav className={`fixed top-0 left-0 right-0 z-50 px-6 py-4 transition-all duration-300 ${navLight ? 'bg-transparent' : 'bg-white/95 backdrop-blur-md border-b border-gray-200/50 shadow-sm'}`}>
                    <div className="max-w-7xl mx-auto flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <img
                                src="/assets/logos/size.webp"
                                alt="Addy Logo"
                                className={`h-14 w-auto object-contain transition-all duration-300 ${navLight ? 'brightness-0 invert' : ''}`}
                            />
                        </div>
                        <div className="hidden md:flex items-center gap-8">
                            <a href="#pricing" className={`font-medium transition-colors text-sm ${navLight ? 'text-white/90 hover:text-white' : 'text-gray-600 hover:text-gray-900'}`}>Pricing</a>
                            <a href="#about" className={`font-medium transition-colors text-sm ${navLight ? 'text-white/90 hover:text-white' : 'text-gray-600 hover:text-gray-900'}`}>About</a>
                            <a href="#faq" className={`font-medium transition-colors text-sm ${navLight ? 'text-white/90 hover:text-white' : 'text-gray-600 hover:text-gray-900'}`}>FAQ</a>
                        </div>
                        <div className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className={`px-5 py-2.5 rounded-full font-semibold text-sm transition-all duration-300 ${navLight ? 'bg-white/20 text-white hover:bg-white/30 border border-white/30' : 'bg-teal-800 text-white hover:bg-teal-700 border border-transparent'}`}
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
                                        className={`px-5 py-2.5 rounded-full font-semibold text-sm transition-all duration-300 ${navLight ? 'bg-white/20 text-white hover:bg-white/30 border border-white/30' : 'bg-teal-800 text-white hover:bg-teal-700 border border-transparent'}`}
                                    >
                                        Get Started
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section - KEEP AS-IS */}
                <main className="relative z-10">
                    <section className="relative overflow-hidden -mt-16 pt-16">
                        <div className="relative w-full min-h-[85vh] pt-5 pb-[70px]">
                            <div
                                className="absolute inset-0 bg-cover bg-center bg-fixed"
                                style={{ backgroundImage: "url('/images/hero.webp')" }}
                            />
                            <div className="absolute inset-0 bg-teal-900/30" />
                            <div
                                className="absolute inset-0 flex flex-col items-end justify-center pt-32 pb-20 px-8 md:px-16 lg:px-24"
                                style={{ fontFamily: "'Gochi Hand', cursive" }}
                            >
                                <div className="text-right space-y-0.5 md:space-y-1 leading-tight mt-20">
                                    <p className="text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-white line-through drop-shadow-lg">Accountant</p>
                                    <p className="text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-white line-through drop-shadow-lg">Analyst</p>
                                    <p className="text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-[#7DDBA3] font-semibold drop-shadow-lg">Business Owner</p>
                                </div>
                                <p className="text-base md:text-lg text-white/95 max-w-md text-right mt-10 drop-shadow-md" style={{ fontFamily: "'Noir Pro', 'DM Sans', sans-serif" }}>
                                    Invoices, receipts, cashflow, reports, tax readiness. Addy keeps your money and operations organised in one place so you can focus on the work that actually makes you money.
                                </p>
                                <Link
                                    href={auth.user ? route('dashboard') : route('register')}
                                    className="mt-8 mb-20 px-8 py-4 rounded-full bg-white/20 text-white font-normal text-lg hover:bg-white/30 backdrop-blur-sm transition-all duration-300 flex items-center justify-center gap-2 border border-white/40"
                                    style={{ fontFamily: "'Noir Pro', 'DM Sans', sans-serif" }}
                                >
                                    {auth.user ? 'Go to Dashboard' : 'Manage your business better'} <ArrowRight className="w-5 h-5" />
                                </Link>
                            </div>
                        </div>

                        {/* Stats Bar - Finovate-style */}
                        <div className="bg-white border-b border-gray-100">
                            <div className="max-w-7xl mx-auto px-6 py-12">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                                    {statItems.map((stat, i) => (
                                        <div key={i} className="text-center md:text-left">
                                            <p className="text-sm font-medium text-teal-600 mb-1">{stat.label}</p>
                                            <p className="text-3xl md:text-4xl font-bold text-gray-900">{stat.value}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Dashboard Section - KEEP */}
                    <section id="about" className="py-24 px-6 bg-gray-50 scroll-mt-24">
                        <div className="max-w-7xl mx-auto">
                            <div className="text-center mb-16">
                                <p className="text-sm font-semibold text-teal-600 uppercase tracking-wider mb-3">Who we are</p>
                                <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4" style={{ fontFamily: "'Gochi Hand', cursive" }}>
                                    Built for Business Owners Who'd Rather Focus on Their Craft
                                </h2>
                                <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                                    You got in business to do great things. Let us handle the admin so you can focus on what matters.
                                </p>
                            </div>
                            <div className="max-w-5xl mx-auto">
                                <div className="rounded-2xl overflow-hidden shadow-xl border border-gray-100">
                                    <img
                                        src="/images/dashboard-screenshot.png"
                                        alt="Addy Dashboard - Revenue, expenses, cash flow, and business progress at a glance"
                                        className="w-full h-auto object-contain block"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mt-16 max-w-4xl mx-auto">
                                {[
                                    "We're a team dedicated to making your life easier—anticipating needs before you ask.",
                                    "Our purpose is clear: help you streamline, protect, and grow what you've built.",
                                    "We help you realize your vision with confidence and peace of mind.",
                                ].map((text, i) => (
                                    <div key={i} className="text-center">
                                        <span className="text-4xl font-bold text-teal-600/30">0{i + 1}</span>
                                        <p className="text-gray-600 mt-2">{text}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Pain Point Cards */}
                    <section className="py-24 px-6 bg-white">
                        <div className="max-w-7xl mx-auto">
                            <div className="text-center mb-16">
                                <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4" style={{ fontFamily: "'Gochi Hand', cursive" }}>
                                    Business should <span className="text-[#7DDBA3]">NOT</span> be Difficult.
                                </h2>
                                <p className="text-lg text-gray-500 max-w-2xl mx-auto">
                                    You got in business to do great things. Well, do them. Let us focus on the admin, you focus on the craft.
                                </p>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">
                                {painPointCards.map((item, idx) => (
                                    <div
                                        key={idx}
                                        className="group rounded-2xl bg-emerald-50 hover:bg-emerald-100 hover:shadow-xl hover:shadow-emerald-100 border border-transparent hover:border-emerald-100 transition-all duration-300 overflow-hidden flex flex-col h-full"
                                    >
                                        <div className="flex-1 min-h-[200px] flex items-center justify-center overflow-hidden bg-emerald-100/50">
                                            <img
                                                src={item.image}
                                                alt={item.title}
                                                className="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                            />
                                        </div>
                                        <div className="p-6">
                                            <h3 className="text-xl font-bold text-gray-900 mb-1" style={{ fontFamily: "'Gochi Hand', cursive" }}>
                                                {item.title}
                                            </h3>
                                            <p className="text-[#7DDBA3] font-semibold text-sm mb-3">
                                                {item.subtitle} <span className="text-gray-900">Addy it.</span>
                                            </p>
                                            <p className="text-gray-500 text-sm leading-relaxed">{item.description}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Pricing */}
                    <section id="pricing" className="py-24 px-6 bg-teal-800 scroll-mt-24">
                        <div className="max-w-6xl mx-auto">
                            <div className="text-center mb-16">
                                <p className="text-sm font-semibold text-teal-200 uppercase tracking-wider mb-3">Pricing</p>
                                <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
                                    Simple, Transparent Pricing
                                </h2>
                                <p className="text-lg text-teal-100 max-w-2xl mx-auto">
                                    Choose the plan that fits your business. Upgrade or downgrade anytime.
                                </p>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="p-8 rounded-2xl bg-white border border-gray-200 flex flex-col">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">Starter</h3>
                                    <div className="text-4xl font-bold text-gray-900 mb-1">K500<span className="text-base text-gray-400 font-normal">/mo</span></div>
                                    <p className="text-gray-600 text-sm mb-8">Essential tools for small businesses.</p>
                                    <ul className="space-y-3 mb-8 flex-1">
                                        {["Basic Accounting", "Invoicing & Quotes", "Up to 3 Users", "Email Support"].map((f, i) => (
                                            <li key={i} className="flex items-start gap-3 text-sm text-gray-600">
                                                <CheckCircle2 className="w-4 h-4 text-teal-500 mt-0.5 shrink-0" />
                                                {f}
                                            </li>
                                        ))}
                                    </ul>
                                    <Link href={route('register')} className="block w-full py-3 px-6 rounded-full border border-gray-200 text-gray-700 font-semibold text-center hover:bg-gray-50 transition-colors text-sm">
                                        Get Started
                                    </Link>
                                </div>
                                <div className="p-8 rounded-2xl bg-white/10 backdrop-blur-sm border-2 border-[#7DDBA3] text-white relative flex flex-col">
                                    <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[#7DDBA3] text-gray-900 px-3 py-1 rounded-full text-xs font-bold">
                                        Most Popular
                                    </div>
                                    <h3 className="text-xl font-bold mb-2">Growth</h3>
                                    <div className="text-4xl font-bold text-[#7DDBA3] mb-1">K2,000<span className="text-base text-teal-200 font-normal">/mo</span></div>
                                    <p className="text-teal-100 text-sm mb-8">For growing teams needing more power.</p>
                                    <ul className="space-y-3 mb-8 flex-1">
                                        {["Advanced Reporting", "Inventory Management", "Up to 10 Users", "Payroll (Basic)", "Priority Support"].map((f, i) => (
                                            <li key={i} className="flex items-start gap-3 text-sm text-teal-100">
                                                <CheckCircle2 className="w-4 h-4 text-[#7DDBA3] mt-0.5 shrink-0" />
                                                {f}
                                            </li>
                                        ))}
                                    </ul>
                                    <Link href={route('register')} className="block w-full py-3 px-6 rounded-full bg-[#7DDBA3] text-gray-900 font-semibold text-center hover:bg-[#6BCF91] transition-colors text-sm">
                                        Start Free Trial
                                    </Link>
                                </div>
                                <div className="p-8 rounded-2xl bg-white border border-gray-200 flex flex-col">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">Full Suite</h3>
                                    <div className="text-4xl font-bold text-gray-900 mb-1">K5,000<span className="text-base text-gray-400 font-normal">/mo</span></div>
                                    <p className="text-gray-600 text-sm mb-8">Complete business management solution.</p>
                                    <ul className="space-y-3 mb-8 flex-1">
                                        {["All Features", "Unlimited Users", "Advanced HR & Payroll", "CRM & Sales", "Multi-Currency", "Dedicated Manager"].map((f, i) => (
                                            <li key={i} className="flex items-start gap-3 text-sm text-gray-600">
                                                <CheckCircle2 className="w-4 h-4 text-teal-500 mt-0.5 shrink-0" />
                                                {f}
                                            </li>
                                        ))}
                                    </ul>
                                    <Link href={route('register')} className="block w-full py-3 px-6 rounded-full border border-gray-200 text-gray-700 font-semibold text-center hover:bg-gray-50 transition-colors text-sm">
                                        Get Started
                                    </Link>
                                </div>
                            </div>
                            <div className="text-center mt-12">
                                <Link href="/enterprise" className="inline-flex items-center gap-2 text-teal-200 hover:text-white font-medium text-sm transition-colors group">
                                    <Shield className="w-4 h-4" />
                                    Looking for Enterprise?
                                    <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </Link>
                            </div>
                        </div>
                    </section>

                    {/* FAQ - Finovate-style accordion */}
                    <section id="faq" className="py-24 px-6 bg-white scroll-mt-24">
                        <div className="max-w-3xl mx-auto">
                            <div className="text-center mb-16">
                                <p className="text-sm font-semibold text-teal-600 uppercase tracking-wider mb-3">FAQ</p>
                                <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                                    Common Questions on Business Management
                                </h2>
                            </div>
                            <div className="space-y-4">
                                {faqs.map((faq, i) => (
                                    <div
                                        key={i}
                                        className="border border-gray-200 rounded-xl overflow-hidden"
                                    >
                                        <button
                                            onClick={() => setOpenFaq(openFaq === i ? null : i)}
                                            className="w-full px-6 py-4 flex items-center justify-between text-left font-semibold text-gray-900 hover:bg-gray-50 transition-colors"
                                        >
                                            {faq.q}
                                            {openFaq === i ? <ChevronUp className="w-5 h-5 text-gray-500" /> : <ChevronDown className="w-5 h-5 text-gray-500" />}
                                        </button>
                                        {openFaq === i && (
                                            <div className="px-6 pb-4 text-gray-600 text-sm leading-relaxed border-t border-gray-100 pt-4">
                                                {faq.a}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* CTA */}
                    <section className="py-24 px-6 bg-emerald-50">
                        <div className="max-w-4xl mx-auto text-center">
                            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                                Ready to Take Control?
                            </h2>
                            <p className="text-lg text-gray-600 mb-8 max-w-xl mx-auto">
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
                            <p className="mt-6 text-teal-600 font-semibold">Don't fear it. Addy it.</p>
                        </div>
                    </section>
                </main>

                {/* Footer - Finovate-style comprehensive */}
                <footer className="py-16 px-6 bg-teal-800 text-white">
                    <div className="max-w-7xl mx-auto">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                            <div>
                                <img src="/assets/logos/size.webp" alt="Addy Logo" className="h-10 w-auto object-contain brightness-0 invert mb-4" />
                                <p className="text-gray-400 text-sm">
                                    Your intelligent business COO. Manage finances, sales, team, and inventory in one place.
                                </p>
                            </div>
                            <div>
                                <h4 className="font-semibold text-white mb-4">Product</h4>
                                <ul className="space-y-2">
                                    <li><a href="#pricing" className="text-gray-400 hover:text-white text-sm transition-colors">Pricing</a></li>
                                    <li><Link href={route('register')} className="text-gray-400 hover:text-white text-sm transition-colors">Get Started</Link></li>
                                    <li><a href="/enterprise" className="text-gray-400 hover:text-white text-sm transition-colors">Enterprise</a></li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-semibold text-white mb-4">Company</h4>
                                <ul className="space-y-2">
                                    <li><a href="#about" className="text-gray-400 hover:text-white text-sm transition-colors">About</a></li>
                                    <li><a href="#faq" className="text-gray-400 hover:text-white text-sm transition-colors">FAQ</a></li>
                                    <li><a href="/privacy" className="text-gray-400 hover:text-white text-sm transition-colors">Privacy</a></li>
                                    <li><a href="/terms" className="text-gray-400 hover:text-white text-sm transition-colors">Terms</a></li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-semibold text-white mb-4">Contact</h4>
                                <p className="text-gray-400 text-sm">Get in touch for support or enterprise inquiries.</p>
                            </div>
                        </div>
                        <div className="pt-8 border-t border-teal-700 flex flex-col md:flex-row items-center justify-between gap-4">
                            <p className="text-teal-200 text-sm">&copy; {new Date().getFullYear()} Addy Business. All rights reserved.</p>
                            <div className="flex items-center gap-6">
                                <a href="/privacy" className="text-teal-200 hover:text-white text-sm transition-colors">Privacy Policy</a>
                                <a href="/terms" className="text-teal-200 hover:text-white text-sm transition-colors">Terms of Service</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
