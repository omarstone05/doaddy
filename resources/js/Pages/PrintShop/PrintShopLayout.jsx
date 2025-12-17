import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Navigation } from '@/Components/layout/Navigation';
import FlashMessages from '@/Components/FlashMessages';
import { 
    Printer, 
    Package, 
    Droplets, 
    Tag, 
    ClipboardList,
    Calculator,
    BarChart3,
    Settings
} from 'lucide-react';
import { cn } from '@/lib/utils';

const printShopNav = [
    { name: 'Overview', href: '/print-shop', icon: Printer, exact: true },
    { name: 'Calculator', href: '/print-shop/jobs/create', icon: Calculator },
    { name: 'Jobs', href: '/print-shop/jobs', icon: ClipboardList },
    { name: 'Materials', href: '/print-shop/materials', icon: Package },
    { name: 'Ink Configs', href: '/print-shop/ink-configs', icon: Droplets },
    { name: 'Pricing Rules', href: '/print-shop/pricing-rules', icon: Tag },
];

export default function PrintShopLayout({ children, title }) {
    const { url } = usePage().props;
    const currentPath = (url || (typeof window !== 'undefined' ? window.location.pathname : '')).replace(/\/$/, '') || '/';

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-zinc-100">
            <Navigation />
            <FlashMessages />
            
            <div className="max-w-[1600px] mx-auto px-6 py-8">
                {/* Hero Header */}
                <div className="relative overflow-hidden rounded-3xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 p-8 mb-8">
                    <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.08%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-30"></div>
                    <div className="relative z-10 flex items-center gap-4">
                        <div className="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <Printer className="w-8 h-8 text-white" strokeWidth={1.5} />
                        </div>
                        <div>
                            <h1 className="text-3xl font-black text-white tracking-tight">Print Shop</h1>
                            <p className="text-white/80 mt-1">Calculate costs, manage materials, and track print jobs</p>
                        </div>
                    </div>
                </div>

                {/* Internal Navigation */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 mb-8 overflow-hidden">
                    <div className="flex overflow-x-auto scrollbar-hide">
                        {printShopNav.map((item) => {
                            const isActive = item.exact 
                                ? currentPath === item.href 
                                : currentPath.startsWith(item.href);
                            
                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={cn(
                                        'flex items-center gap-2 px-6 py-4 text-sm font-semibold whitespace-nowrap transition-all border-b-2',
                                        isActive
                                            ? 'border-violet-500 text-violet-600 bg-violet-50/50'
                                            : 'border-transparent text-gray-600 hover:text-violet-600 hover:bg-violet-50/30'
                                    )}
                                >
                                    <item.icon className="w-4 h-4" />
                                    {item.name}
                                </Link>
                            );
                        })}
                    </div>
                </div>

                {/* Page Content */}
                {children}
            </div>
        </div>
    );
}

