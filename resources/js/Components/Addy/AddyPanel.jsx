import React from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import AddyChat from './AddyChat';
import AddyInsights from './AddyInsights';

export default function AddyPanel() {
    const addyContext = useAddy();
    
    // If context is not available, don't render
    if (!addyContext) {
        return null;
    }
    
    const { isOpen, panelView } = addyContext;

    if (!isOpen) return null;

    return panelView === 'chat' ? <AddyChat /> : <AddyInsights />;
}
