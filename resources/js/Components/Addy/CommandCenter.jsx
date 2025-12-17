import React, { useEffect } from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import AddyChat from './AddyChat';
import AddyInsights from './AddyInsights';

export default function CommandCenter() {
    const addyContext = useAddy();

    // If context is not available, don't render
    if (!addyContext) {
        return null;
    }

    const { toggleAddy, panelView, isOpen } = addyContext;
    
    // Debug: log when isOpen changes
    React.useEffect(() => {
        console.log('CommandCenter: isOpen changed to', isOpen, 'panelView:', panelView);
    }, [isOpen, panelView]);

    useEffect(() => {
        const handleKeyDown = (e) => {
            // Cmd+K or Ctrl+K to toggle
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                toggleAddy();
            }

            // Esc to close (handled by AddyChat/AddyInsights components usually, but good to have backup)
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [toggleAddy]);

    // Render the appropriate view based on panelView state and isOpen
    if (!isOpen) {
        return null;
    }
    
    return (
        <>
            {panelView === 'chat' && <AddyChat />}
            {panelView === 'insights' && <AddyInsights />}
        </>
    );
}
