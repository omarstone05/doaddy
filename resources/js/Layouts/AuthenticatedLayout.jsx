import React from 'react';
import { usePage } from '@inertiajs/react';
import { Navigation } from '@/Components/layout/Navigation';
import FlashMessages from '@/Components/FlashMessages';

export default function AuthenticatedLayout({ children, header = null }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-50">
            <Navigation />
            <FlashMessages />
            <main className="max-w-[1600px] mx-auto">
                {children}
            </main>
        </div>
    );
}
