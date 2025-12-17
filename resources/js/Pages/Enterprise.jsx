import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Building2, CheckCircle2, LayoutDashboard, Mail, MessageSquare, ShieldCheck, Users, Zap } from 'lucide-react';

export default function Enterprise({ auth }) {
    return (
        <>
            <Head title="Enterprise Solutions - Addy" />

            <div className="min-h-screen relative overflow-hidden font-sans text-gray-900 selection:bg-teal-500 selection:text-white bg-gray-50">
                {/* Background Gradients */}
                <div className="absolute inset-0 z-0 pointer-events-none">
                    <div className="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-teal-200/20 blur-[120px]"></div>
                    <div className="absolute bottom-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-blue-100/30 blur-[120px]"></div>
                </div>

                {/* Navbar */}
                <nav className="relative z-50 px-6 py-6">
                    <div className="max-w-7xl mx-auto flex items-center justify-between">
                        <Link href="/" className="flex items-center gap-2 group">
                            <ArrowLeft className="w-5 h-5 text-gray-500 group-hover:text-teal-600 transition-colors" />
                            <span className="text-gray-600 font-medium group-hover:text-teal-700 transition-colors">Back to Home</span>
                        </Link>
                        <img src="/assets/logos/size.png" alt="Addy Logo" className="h-8 w-auto object-contain" />
                    </div>
                </nav>

                <main className="relative z-10 pt-12 pb-24 px-6">
                    <div className="max-w-7xl mx-auto">
                        {/* Hero Section */}
                        <div className="text-center mb-20">
                            <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-teal-50 border border-teal-100 mb-6">
                                <Building2 className="w-4 h-4 text-teal-600" />
                                <span className="text-sm font-medium text-teal-800">Enterprise Solutions</span>
                            </div>
                            <h1 className="text-4xl md:text-6xl font-bold tracking-tight text-gray-900 mb-6">
                                Scale your organization <br />
                                <span className="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-blue-600">
                                    without limits
                                </span>
                            </h1>
                            <p className="text-xl text-gray-600 max-w-2xl mx-auto mb-10">
                                Custom-tailored solutions for large organizations with complex needs. Get dedicated support, advanced security, and seamless integrations.
                            </p>
                            <a
                                href="#contact"
                                className="inline-flex items-center gap-2 px-8 py-4 rounded-2xl bg-teal-600 text-white font-bold text-lg shadow-xl shadow-teal-500/20 hover:bg-teal-700 hover:shadow-2xl hover:shadow-teal-500/30 hover:-translate-y-1 transition-all duration-300"
                            >
                                Contact Sales <Mail className="w-5 h-5" />
                            </a>
                        </div>

                        {/* Features Grid */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-32">
                            {[
                                {
                                    title: "Custom Integrations",
                                    desc: "Connect Addy with your existing ERP, CRM, and HR systems seamlessly.",
                                    icon: <Zap className="w-6 h-6 text-teal-600" />
                                },
                                {
                                    title: "Enterprise Security",
                                    desc: "SSO, Audit Logs, and Role-Based Access Control (RBAC) for total control.",
                                    icon: <ShieldCheck className="w-6 h-6 text-blue-600" />
                                },
                                {
                                    title: "Dedicated Support",
                                    desc: "24/7 priority support with a dedicated account manager for your team.",
                                    icon: <Users className="w-6 h-6 text-mint-600" />
                                }
                            ].map((feature, idx) => (
                                <div key={idx} className="bg-white/60 backdrop-blur-md p-8 rounded-3xl border border-white/60 shadow-sm hover:shadow-md transition-all">
                                    <div className="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center mb-6">
                                        {feature.icon}
                                    </div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-3">{feature.title}</h3>
                                    <p className="text-gray-600 leading-relaxed">
                                        {feature.desc}
                                    </p>
                                </div>
                            ))}
                        </div>

                        {/* Contact Form Section */}
                        <div id="contact" className="max-w-4xl mx-auto">
                            <div className="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden flex flex-col md:flex-row">
                                <div className="p-10 md:w-1/2 bg-gradient-to-br from-teal-600 to-teal-800 text-white flex flex-col justify-between">
                                    <div>
                                        <h2 className="text-3xl font-bold mb-4">Let's talk business</h2>
                                        <p className="text-teal-100 mb-8">
                                            Fill out the form and our sales team will get back to you within 24 hours.
                                        </p>

                                        <div className="space-y-6">
                                            <div className="flex items-center gap-4">
                                                <div className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                                                    <Mail className="w-5 h-5" />
                                                </div>
                                                <div>
                                                    <div className="text-sm text-teal-200">Email us</div>
                                                    <div className="font-medium">sales@addy.com</div>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-4">
                                                <div className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                                                    <MessageSquare className="w-5 h-5" />
                                                </div>
                                                <div>
                                                    <div className="text-sm text-teal-200">Live Chat</div>
                                                    <div className="font-medium">Available 9am - 5pm CAT</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-12">
                                        <div className="flex items-center gap-2 text-sm text-teal-200">
                                            <ShieldCheck className="w-4 h-4" />
                                            <span>Your data is secure with us.</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="p-10 md:w-1/2 bg-white">
                                    <form className="space-y-6" onSubmit={(e) => e.preventDefault()}>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                            <input type="text" className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all" placeholder="John Doe" />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">Work Email</label>
                                            <input type="email" className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all" placeholder="john@company.com" />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">Company Size</label>
                                            <select className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all">
                                                <option>50-100 employees</option>
                                                <option>100-500 employees</option>
                                                <option>500+ employees</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">Message</label>
                                            <textarea className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all h-32 resize-none" placeholder="Tell us about your needs..."></textarea>
                                        </div>
                                        <button className="w-full py-4 rounded-xl bg-teal-600 text-white font-bold shadow-lg shadow-teal-500/20 hover:bg-teal-700 hover:shadow-teal-500/30 transition-all">
                                            Request Consultation
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                <footer className="py-12 text-center text-gray-500 text-sm border-t border-gray-200 bg-white">
                    <div className="max-w-7xl mx-auto px-6">
                        <p>&copy; {new Date().getFullYear()} Addy Business. All rights reserved.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
