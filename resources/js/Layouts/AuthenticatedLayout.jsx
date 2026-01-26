import React from 'react';
import { usePage } from '@inertiajs/react';
import AppSwitcher from '@/Components/AppSwitcher';
import { Navigation } from '@/Components/layout/Navigation';
import FlashMessages from '@/Components/FlashMessages';
import Footer from '@/Components/layout/Footer';

export default function AuthenticatedLayout({ children, header = null }) {
    const { appSwitcher } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col">
            <Navigation />
            {appSwitcher?.currentApp && (
                <div className="max-w-[1600px] mx-auto w-full px-4 pt-3 flex justify-end">
                    <AppSwitcher
                        currentApp={appSwitcher.currentApp}
                        availableApps={appSwitcher.availableApps}
                    />
                </div>
            )}
            <FlashMessages />
            <main className="max-w-[1600px] mx-auto flex-1 w-full">
                {children}
            </main>
            <Footer />
        </div>
    );
}
