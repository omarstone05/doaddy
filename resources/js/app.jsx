import React from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { AddyProvider } from './Contexts/AddyContext';
import AddyBubble from './Components/Addy/AddyBubble';
import AddyPanel from './Components/Addy/AddyPanel';

const appName = import.meta.env.VITE_APP_NAME || 'Addy';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: async (name) => {
        const page = await resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx'));
        
        // Wrap each page component with AddyProvider
        // This ensures AddyProvider is inside the Inertia App context
        return (props) => {
            const PageComponent = page.default || page;
            return (
                <AddyProvider>
                    <PageComponent {...props} />
                    <AddyBubble />
                    <AddyPanel />
                </AddyProvider>
            );
        };
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#00635D',
    },
});

