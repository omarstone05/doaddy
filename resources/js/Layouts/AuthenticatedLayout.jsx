import React from 'react';
import { usePage } from '@inertiajs/react';
import { Navigation } from '@/Components/layout/Navigation';
import FlashMessages from '@/Components/FlashMessages';
import Footer from '@/Components/layout/Footer';

export default function AuthenticatedLayout({ children, header = null }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col">
            <Navigation />
            <FlashMessages />
            <main className="max-w-[1600px] mx-auto flex-1 w-full">
                {children}
            </main>
            <Footer />
        </div>
    );
}
