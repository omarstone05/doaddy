import React from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import AddyChat from './AddyChat';
import AddyInsights from './AddyInsights';

export default function AddyPanel() {
    const { isOpen, panelView } = useAddy();

    if (!isOpen) return null;

    return panelView === 'chat' ? <AddyChat /> : <AddyInsights />;
}
